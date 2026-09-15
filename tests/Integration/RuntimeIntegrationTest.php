<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\Component\ProcessRunner\ProcessRunner;
use Gaambo\DeployerWordpress\DdevRuntime;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Runtime;
use PHPUnit\Framework\MockObject\MockObject;

class RuntimeIntegrationTest extends IntegrationTestCase
{
    private MockObject $processRunnerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processRunnerMock = $this->createMock(ProcessRunner::class);
        $this->deployer['processRunner'] = $this->processRunnerMock;
    }

    public function testRunFallsBackToNativeLocalhostExecution(): void
    {
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) {
                $this->assertSame('wp core version', $command);
                $this->assertSame('/var/www/current', $this->runCwd($options));
                return '6.8';
            });

        $result = Runtime::run('{{bin/wp}} core version', ['cwd' => '/var/www/current']);

        $this->assertSame('6.8', $result);
    }

    public function testDdevMapsCwdAndUsesRuntimeBinary(): void
    {
        $this->host->set('runtime', new DdevRuntime('/var/www/html'));
        $this->host->set('bin/wp', '/host/bin/wp');

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) {
                $this->assertSame('ddev exec --dir /var/www/html/current wp core version', $command);
                $this->assertSame('/var/www', $this->runCwd($options));
                return '6.8';
            });

        Runtime::run('{{bin/wp}} core version', ['cwd' => '/var/www/current']);
    }

    public function testDdevMapsExplicitPathsAndSupportsProjectRootOverride(): void
    {
        $runtime = new DdevRuntime('/var/www/html', projectRoot: '/custom/project');
        $this->host->set('runtime', $runtime);

        $this->assertSame('/var/www/html/data/dump.sql', Runtime::path('/custom/project/data/dump.sql'));
    }

    public function testDdevRejectsPathsOutsideProjectRoot(): void
    {
        $this->host->set('runtime', new DdevRuntime('/var/www/html'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside project root "/var/www"');

        Runtime::path('/tmp/dump.sql');
    }

    public function testDdevRuntimeConfigCanBeOverridden(): void
    {
        $this->host->set('runtime', new DdevRuntime(config: ['bin/wp' => '/custom/bin/wp']));

        $this->assertSame('/custom/bin/wp', Runtime::getConfig('bin/wp'));
        $this->assertSame('composer', Runtime::getConfig('bin/composer'));
        $this->assertSame('npm', Runtime::getConfig('bin/npm'));
        $this->assertSame('php', Runtime::getConfig('bin/php'));
        $this->assertSame('/var/www/current', Runtime::getConfig('current_path'));
    }

    public function testWithinInterceptsLocalhostRunAndRestoresContext(): void
    {
        $this->host->set('runtime', new DdevRuntime('/var/www/html'));

        $this->processRunnerMock
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnCallback(function ($host, $command) {
                static $call = 0;
                $expected = [
                    'ddev exec --dir /var/www/html/current wp plugin status',
                    'ddev exec wp theme status',
                    'wp core version',
                ];
                $this->assertSame($expected[$call++], $command);
                return '';
            });

        Runtime::within(function () {
            Runtime::within(function () {
                Localhost::run('{{bin/wp}} plugin status', ['cwd' => '/var/www/current']);
            });
            Localhost::run('{{bin/wp}} theme status');
        });
        Localhost::run('{{bin/wp}} core version');
    }

    public function testWithinRestoresContextWhenCallbackFails(): void
    {
        $this->host->set('runtime', new DdevRuntime('/var/www/html'));
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
    }
}
