<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\ProcessRunner\ProcessRunner;
use Deployer\Host\Host;
use Deployer\Host\Localhost as DeployerLocalhost;
use Deployer\Ssh\RunParams;
use Deployer\Ssh\SshClient;
use Deployer\Task\Context;
use Gaambo\DeployerWordpress\WPCLI;
use Gaambo\DeployerUtils\Runtime\DdevRuntime;
use PHPUnit\Framework\MockObject\MockObject;

use function Gaambo\DeployerUtils\runtime;

class WpCliIntegrationTest extends IntegrationTestCase
{
    private MockObject $processRunnerMock;
    private MockObject $sshClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processRunnerMock = $this->createMock(ProcessRunner::class);
        $this->deployer['processRunner'] = $this->processRunnerMock;
        $this->sshClientMock = $this->createMock(SshClient::class);
        $this->deployer['sshClient'] = $this->sshClientMock;
    }

    public function testRunCommand(): void
    {
        $path = '/var/www/html';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = 'wp post list --format=table';
        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandWithoutPath(): void
    {
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp post list --format=table";

        $this->expectLocalWpCommand($expectedCommand, null);

        WPCLI::runCommand($command, null, $arguments);
    }

    public function testRunCommandLocally(): void
    {
        $expectedCommand = 'wp post list --format=table ';
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($expectedCommand) {
                $this->assertEquals($expectedCommand, $command);
                $this->assertSame('/var/www/current', $this->runCwd($options));
                return '';
            });

        WPCLI::runCommandLocally('post list --format=table');
    }

    public function testRunCommandLocallyWithoutPath(): void
    {
        $expectedCommand = 'wp post list --format=table ';
        // Note: We can't test for the host object because runLocally creates a new host instance.
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($expectedCommand) {
                $this->assertEquals($expectedCommand, $command);
                return '';
            });

        WPCLI::runCommandLocally('post list --format=table', false);
    }

    public function testRunCommandLocallyMapsOnlyExplicitRuntimePaths(): void
    {
        $this->host->set('runtime', runtime(DdevRuntime::class));
        $hostDumpPath = '/var/www/data/dumps/site.sql';

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) {
                $this->assertSame(
                    'wp db import /var/www/html/data/dumps/site.sql --source=/var/www/uploads',
                    $command
                );
                $this->assertSame('/var/www', $this->runCwd($options));
                $this->assertSame($this->ddevShell('/var/www/html/current'), $this->runShell($options));
                return '';
            });

        WPCLI::runCommandLocally(
            "db import $hostDumpPath",
            '/var/www/current',
            '--source=/var/www/uploads',
            runtimePaths: [$hostDumpPath]
        );
    }

    public function testRunCommandLocallyUsesLocalhostFromAnotherTaskContext(): void
    {
        $this->host->set('runtime', runtime(DdevRuntime::class));
        $remoteHost = new Host('mi6');
        $remoteHost->set('deploy_path', '/remote/project');
        $remoteHost->set('runtime', runtime(DdevRuntime::class));
        Context::push(new Context($remoteHost));

        try {
            $this->processRunnerMock
                ->expects($this->once())
                ->method('run')
                ->willReturnCallback(function ($host, $command, $options) {
                    $this->assertSame('wp db import /var/www/html/data/dump.sql ', $command);
                    $this->assertSame('/var/www', $this->runCwd($options));
                    $this->assertSame($this->ddevShell('/var/www/html/current'), $this->runShell($options));
                    return '';
                });

            WPCLI::runCommandLocally(
                'db import /var/www/data/dump.sql',
                '/var/www/current',
                runtimePaths: ['/var/www/data/dump.sql']
            );
        } finally {
            Context::pop();
        }
    }

    public function testRunCommandUsesRemoteSourceHostWithoutRuntime(): void
    {
        $remoteSourceHost = $this->remoteSourceHost();
        $this->sshClientMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($executionHost, $command, RunParams $options) use ($remoteSourceHost) {
                $this->assertSame($remoteSourceHost, $executionHost);
                $this->assertSame('wp post list --format=json', $command);
                $this->assertSame('/srv/www/current', $this->runCwd($options));
                return '';
            });

        $this->onHost(
            $remoteSourceHost,
            fn() => WPCLI::runCommand('post list', arguments: '--format=json')
        );
    }

    public function testRunCommandUsesDdevOnRemoteSourceHostAndMapsExplicitPaths(): void
    {
        $remoteSourceHost = $this->remoteSourceHost();
        $remoteSourceHost->set('runtime', runtime('ddev'));
        $sourceDumpFile = '/srv/www/data/dumps/site.sql';
        $this->sshClientMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($executionHost, $command, RunParams $options) use ($remoteSourceHost) {
                $this->assertNotSame($remoteSourceHost, $executionHost);
                $this->assertSame('production', $executionHost->getAlias());
                $this->assertSame('wp db export /var/www/html/data/dumps/site.sql ', $command);
                $this->assertSame('', $this->runCwd($options));
                $this->assertSame(
                    $this->remoteDdevShell('/srv/www', '/var/www/html/current'),
                    $this->runShell($options)
                );
                return '';
            });

        $this->onHost(
            $remoteSourceHost,
            fn() => WPCLI::runCommand(
                "db export $sourceDumpFile",
                runtimePaths: [$sourceDumpFile]
            )
        );
    }

    public function testInstall(): void
    {
        $installPath = '/usr/local/bin';
        $binaryName = 'wp';
        $expectedCommands = [
            "sudo mkdir -p $installPath",
            "sudo cd $installPath && curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar",
            "sudo mv $installPath/wp-cli.phar $installPath/$binaryName"
        ];

        // Set up expectations for each command in sequence
        $this->processRunnerMock
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($expectedCommands) {
                static $index = 0;
                $this->assertEquals($expectedCommands[$index], $command);
                $index++;
                return 'Installation output';
            });

        $result = WPCLI::install($installPath, $binaryName, true);
        $this->assertEquals("$installPath/$binaryName", $result);
    }

    public function testInstallWithoutSudo(): void
    {
        $installPath = '/usr/local/bin';
        $binaryName = 'wp';
        $expectedCommands = [
            "mkdir -p $installPath",
            "cd $installPath && curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar",
            "mv $installPath/wp-cli.phar $installPath/$binaryName"
        ];

        // Set up expectations for each command in sequence
        $this->processRunnerMock
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($expectedCommands) {
                static $index = 0;
                $this->assertEquals($expectedCommands[$index], $command);
                $index++;
                return 'Installation output';
            });

        $result = WPCLI::install($installPath, $binaryName, false);
        $this->assertEquals("$installPath/$binaryName", $result);
    }

    public function testInstallWithDefaultBinaryName(): void
    {
        $installPath = '/usr/local/bin';
        $expectedCommands = [
            "mkdir -p $installPath",
            "cd $installPath && curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar"
        ];

        // Set up expectations for each command in sequence
        $this->processRunnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($expectedCommands) {
                static $index = 0;
                $this->assertEquals($expectedCommands[$index], $command);
                $index++;
                return 'Installation output';
            });

        $result = WPCLI::install($installPath);
        $this->assertEquals("$installPath/wp-cli.phar", $result);
    }

    public function testRunCommandWithComplexArguments(): void
    {
        $path = '/var/www/html';
        $command = 'post create';
        $arguments = '--post_title="Complex Title with Spaces" --post_content="Content with \'quotes\' and \"double quotes\"" --post_status=draft';
        $expectedCommand = "wp $command $arguments";
        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandWithSpecialCharactersInPath(): void
    {
        $path = '/var/www/html with spaces';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp $command $arguments";
        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandWithVeryLongPath(): void
    {
        // Create a path that's 255 characters long (common filesystem limit)
        $path = str_repeat('a', 200) . '/path/to/wordpress';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp $command $arguments";
        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandWithComplexCommand(): void
    {
        $path = '/var/www/html';
        $command = 'post create --post_type=page --post_status=publish --post_title="Home Page" --post_content="Welcome to our site"';
        $arguments = '--meta_input=\'{"_wp_page_template":"page-home.php"}\'';
        $expectedCommand = "wp $command $arguments";
        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandLocallyWithComplexPath(): void
    {
        $path = '/var/www/html with spaces and special chars @#$%';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp $command $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($expectedCommand, $path) {
                $this->assertEquals($expectedCommand, $command);
                $this->assertSame($path, $this->runCwd($options));
                return '';
            });

        WPCLI::runCommandLocally($command, $path, $arguments);
    }

    public function testRunCommandWithCustomWpBinary(): void
    {
        $path = '/var/www/html';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "/usr/local/bin/wp $command $arguments";

        // Set custom wp binary path
        $this->deployer->config->set('bin/wp', '/usr/local/bin/wp');
        $this->host->config()->set('bin/wp', '/usr/local/bin/wp');

        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandLocallyWithCustomWpBinary(): void
    {
        $path = '/var/www/html';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "/usr/local/bin/wp $command $arguments";

        // Set custom wp binary path for localhost
        $this->deployer->config->set('localhost.bin/wp', '/usr/local/bin/wp');
        $this->host->config()->set('bin/wp', '/usr/local/bin/wp');

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($expectedCommand, $path) {
                $this->assertEquals($expectedCommand, $command);
                $this->assertSame($path, $this->runCwd($options));
                return '';
            });

        WPCLI::runCommandLocally($command, $path, $arguments);
    }

    public function testRunCommandWithInvalidWpBinary(): void
    {
        $path = '/var/www/html';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp $command $arguments";

        // Set invalid wp binary (should fall back to default)
        $this->deployer->config->set('bin/wp', null);

        $this->expectLocalWpCommand($expectedCommand, $path);

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandLocallyWithInvalidWpBinary(): void
    {
        $path = '/var/www/html';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp $command $arguments";

        // Set invalid wp binary for localhost (should fall back to default)
        $this->deployer->config->set('localhost.bin/wp', null);

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options) use ($expectedCommand, $path) {
                $this->assertEquals($expectedCommand, $command);
                $this->assertSame($path, $this->runCwd($options));
                return '';
            });

        WPCLI::runCommandLocally($command, $path, $arguments);
    }

    public function testInstallWithCustomBinaryPath(): void
    {
        $installPath = '/usr/local/bin';
        $binaryName = 'wp';
        $expectedCommands = [
            "sudo mkdir -p $installPath",
            "sudo cd $installPath && curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar",
            "sudo mv $installPath/wp-cli.phar $installPath/$binaryName"
        ];

        // Set custom binary path
        $this->deployer->config->set('bin/wp', "$installPath/$binaryName");

        $this->processRunnerMock
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($expectedCommands) {
                static $index = 0;
                $this->assertEquals($expectedCommands[$index], $command);
                $index++;
                return 'Installation output';
            });

        $result = WPCLI::install($installPath, $binaryName, true);
        $this->assertEquals("$installPath/$binaryName", $result);
    }

    private function expectLocalWpCommand(string $expectedCommand, ?string $expectedCwd): void
    {
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (
                $executionHost,
                $command,
                RunParams $options
            ) use (
                $expectedCommand,
                $expectedCwd
            ) {
                $this->assertInstanceOf(DeployerLocalhost::class, $executionHost);
                $this->assertSame($expectedCommand, $command);
                $this->assertSame($expectedCwd, $this->runCwd($options));
                return 'WP-CLI output';
            });
    }

    private function remoteSourceHost(): Host
    {
        $sourceHost = new Host('production');
        $sourceHost->setHostname('example.com');
        $sourceHost->setRemoteUser('deploy');
        $sourceHost->set('deploy_path', '/srv/www');
        $sourceHost->set('current_path', '/srv/www/current');
        $sourceHost->set('release_or_current_path', '/srv/www/current');
        $sourceHost->set('bin/wp', 'wp');

        return $sourceHost;
    }

    private function onHost(Host $sourceHost, callable $callback): mixed
    {
        Context::push(new Context($sourceHost));
        try {
            return $callback();
        } finally {
            Context::pop();
        }
    }
}
