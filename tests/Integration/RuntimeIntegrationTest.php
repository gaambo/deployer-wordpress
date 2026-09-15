<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Host\Localhost as DeployerLocalhost;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Runtime\DdevRuntimeHost;
use Gaambo\DeployerWordpress\Runtime\Runtime;
use PHPUnit\Framework\MockObject\MockObject;

use function Gaambo\DeployerWordpress\runtime;

class RuntimeIntegrationTest extends IntegrationTestCase
{
    private MockObject $processRunnerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processRunnerMock = $this->createMock(ProcessRunner::class);
        $this->deployer['processRunner'] = $this->processRunnerMock;
    }

    public function testFactoryCreatesAnUnregisteredRuntimeBoundToLocalhost(): void
    {
        $this->host->set('current_path', '{{deploy_path}}/current');
        $runtime = runtime(DdevRuntimeHost::class);

        $this->assertInstanceOf(DeployerLocalhost::class, $runtime);
        $this->assertSame('/var/www/html', $runtime->get('deploy_path'));
        $this->assertSame('/var/www/html/current', $runtime->get('current_path'));
        $this->assertSame($this->host, $this->deployer->hosts->get('localhost'));
        $this->assertCount(1, $this->deployer->hosts);
    }

    public function testRunFallsBackToNativeLocalhostExecution(): void
    {
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) {
                $this->assertInstanceOf(DeployerLocalhost::class, $host);
                $this->assertSame('wp core version', $command);
                $this->assertSame('/var/www/current', $this->runCwd($options));
                return '6.8';
            });

        $result = Runtime::run('{{bin/wp}} core version', ['cwd' => '/var/www/current']);

        $this->assertSame('6.8', $result);
    }

    public function testDdevUsesAContainerShellAndHostProjectCwd(): void
    {
        $runtime = runtime(DdevRuntimeHost::class);
        $this->host->set('runtime', $runtime);

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($runtime) {
                $this->assertSame($runtime, $host);
                $this->assertSame('wp core version', $command);
                $this->assertSame('/var/www', $this->runCwd($options));
                $this->assertSame(
                    $this->ddevShell('/var/www/html/current'),
                    $this->runShell($options)
                );
                return '6.8';
            });

        Runtime::run('{{bin/wp}} core version', ['cwd' => '/var/www/current']);
    }

    public function testDdevMapsExplicitPathsFromLocalhostDeployPath(): void
    {
        $this->host->set('runtime', runtime(DdevRuntimeHost::class));

        $this->assertSame('/var/www/html/data/dump.sql', Runtime::path('/var/www/data/dump.sql'));
    }

    public function testDdevRejectsPathsOutsideLocalhostDeployPath(): void
    {
        $this->host->set('runtime', runtime(DdevRuntimeHost::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside project root "/var/www"');

        Runtime::path('/tmp/dump.sql');
    }

    public function testDdevRejectsRelativePaths(): void
    {
        $this->host->set('runtime', runtime(DdevRuntimeHost::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Runtime path must be an absolute path');

        Runtime::path('data/dump.sql');
    }

    public function testInheritedCallableConfigResolvesAndCachesOnRuntime(): void
    {
        $calls = 0;
        $this->host->set('bin/wp', function () use (&$calls) {
            $calls++;
            return \Deployer\which('wp');
        });
        $runtime = runtime(DdevRuntimeHost::class);
        $this->host->set('runtime', $runtime);

        $this->processRunnerMock
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($runtime) {
                if ($host === $runtime) {
                    $this->assertSame($this->ddevShell('/var/www/html'), $this->runShell($options));
                } else {
                    $this->assertSame($this->host, $host);
                }
                return match (str_replace("'", '', $command)) {
                    'command -v wp || which wp || type -p wp' => '/usr/local/bin/wp',
                    '/usr/local/bin/wp core version' => '6.8',
                };
            });

        $this->assertSame('6.8', Runtime::run('{{bin/wp}} core version'));
        $this->assertSame('/usr/local/bin/wp', $runtime->get('bin/wp'));
        $this->assertSame(1, $calls);
        $this->assertSame('/usr/local/bin/wp', $this->host->get('bin/wp'));
        $this->assertSame(2, $calls);
    }

    public function testRuntimeDeployPathCanBeConfiguredFluently(): void
    {
        $this->host->set('current_path', '{{deploy_path}}/current');
        $runtime = runtime(DdevRuntimeHost::class)->set('ddev_deploy_path', '/srv/app');
        $this->host->set('runtime', $runtime);

        $this->assertSame('/srv/app', Runtime::getConfig('deploy_path'));
        $this->assertSame('/srv/app/current', Runtime::getConfig('current_path'));
    }

    public function testDistinctInheritedBinaryCallbacksResolveThroughRuntime(): void
    {
        foreach (['wp', 'composer', 'npm', 'php'] as $binary) {
            $this->host->set("bin/$binary", fn() => \Deployer\which($binary));
        }
        $runtime = runtime(DdevRuntimeHost::class);
        $this->host->set('runtime', $runtime);

        $this->processRunnerMock
            ->expects($this->exactly(4))
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($runtime) {
                $this->assertSame($runtime, $host);
                $this->assertSame($this->ddevShell('/var/www/html'), $this->runShell($options));
                preg_match("/command -v '?(\\w+)'?/", $command, $matches);
                return "/runtime/bin/{$matches[1]}";
            });

        foreach (['wp', 'composer', 'npm', 'php'] as $binary) {
            $this->assertSame("/runtime/bin/$binary", Runtime::getConfig("bin/$binary"));
            $this->assertSame("/runtime/bin/$binary", $runtime->get("bin/$binary"));
        }
    }

    public function testWithinUsesRuntimeContextAndRestoresNestedContexts(): void
    {
        $runtime = runtime(DdevRuntimeHost::class);
        $this->host->set('runtime', $runtime);

        $this->processRunnerMock
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($runtime) {
                static $call = 0;
                $expectedHosts = [$runtime, $runtime, null];
                $expectedCommands = ['wp plugin status', 'wp theme status', 'wp core version'];
                if ($expectedHosts[$call] === null) {
                    $this->assertInstanceOf(DeployerLocalhost::class, $host);
                } else {
                    $this->assertSame($expectedHosts[$call], $host);
                }
                $this->assertSame($expectedCommands[$call++], $command);
                return '';
            });

        Runtime::within(function () {
            Runtime::within(function () {
                Localhost::run('{{bin/wp}} plugin status', ['cwd' => '/var/www/current']);
            });
            Localhost::run('{{bin/wp}} theme status');
        });
        Localhost::run('{{bin/wp}} core version');

        $this->assertFalse(Runtime::isActive());
    }

    public function testWithinRestoresContextWhenCommandFails(): void
    {
        $this->host->set('runtime', runtime(DdevRuntimeHost::class));
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willThrowException(new \RuntimeException('command failed'));

        try {
            Runtime::within(function () {
                Localhost::run('{{bin/wp}} core version');
            });
        } catch (\RuntimeException) {
        }

        $this->assertFalse(Runtime::isActive());
        $this->assertSame($this->host, \Deployer\currentHost());
    }

}
