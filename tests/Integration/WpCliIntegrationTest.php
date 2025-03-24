<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerWordpress\WPCLI;
use PHPUnit\Framework\MockObject\MockObject;

class WpCliIntegrationTest extends IntegrationTestCase
{
    private MockObject $processRunnerMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up the process runner mock
        $this->processRunnerMock = $this->createMock(\Deployer\Component\ProcessRunner\ProcessRunner::class);
        $this->deployer['processRunner'] = $this->processRunnerMock;
    }

    public function testRunCommand(): void
    {
        $path = '/var/www/html';
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "cd $path && wp post list --format=table";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('WP-CLI output');

        WPCLI::runCommand($command, $path, $arguments);
    }

    public function testRunCommandWithoutPath(): void
    {
        $command = 'post list';
        $arguments = '--format=table';
        $expectedCommand = "wp post list --format=table";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('WP-CLI output');

        WPCLI::runCommand($command, null, $arguments);
    }

    public function testRunCommandLocally(): void
    {
        // Note: We can't test for the host object because runLocally creates a new host instance
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->anything(), // Host object is not reliable as runLocally creates a new instance
                'cd /var/www/current && wp post list --format=table ',
                []
            );

        WPCLI::runCommandLocally('post list --format=table');
    }

    public function testRunCommandLocallyWithoutPath(): void
    {
        // Note: We can't test for the host object because runLocally creates a new host instance
        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->anything(), // Host object is not reliable as runLocally creates a new instance
                'wp post list --format=table ',
                []
            );

        WPCLI::runCommandLocally('post list --format=table', false);
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
} 
