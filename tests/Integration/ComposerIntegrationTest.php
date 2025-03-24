<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Component\Ssh\Client;
use Gaambo\DeployerWordpress\Composer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerIntegrationTest extends IntegrationTestCase
{
    private MockObject $processRunnerMock;
    private MockObject $sshClientMock;
    private MockObject $outputMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the processRunner and sshClient
        $this->processRunnerMock = $this->createMock(ProcessRunner::class);
        $this->sshClientMock = $this->createMock(Client::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // Set them in the Deployer container
        $this->deployer['processRunner'] = $this->processRunnerMock;
        $this->deployer['sshClient'] = $this->sshClientMock;
        $this->deployer['output'] = $this->outputMock;

        // Set default composer configuration
        $this->deployer->config->set('composer_action', 'install');
        $this->deployer->config->set('composer_options', '--no-dev --no-interaction');
        $this->deployer->config->set('bin/composer', 'composer');
        $this->deployer->config->set('bin/php', 'php');
        $this->deployer->config->set('deploy_path', '/var/www');
    }

    public function testRunDefault(): void
    {
        $path = '/var/www/html';
        $expectedCommand = "cd $path && composer install --no-dev --no-interaction ";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('Composer output');

        $result = Composer::runDefault($path);
        $this->assertEquals('Composer output', $result);
    }

    public function testRunCommand(): void
    {
        $path = '/var/www/html';
        $command = 'require';
        $arguments = 'package/name:1.0.0';
        $expectedCommand = "cd $path && composer require package/name:1.0.0 ";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('Composer output');

        $result = Composer::runCommand($path, $command, $arguments);
        $this->assertEquals('Composer output', $result);
    }

    public function testRunScript(): void
    {
        $path = '/var/www/html';
        $script = 'post-install-cmd';
        $arguments = '--env=prod';
        $expectedCommand = "cd $path && composer run-script post-install-cmd --env=prod ";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('Script output');

        $result = Composer::runScript($path, $script, $arguments);
        $this->assertEquals('Script output', $result);
    }

    public function testInstall(): void
    {
        $installPath = '/usr/local/bin';
        $binaryName = 'composer';
        $expectedCommands = [
            "sudo mkdir -p $installPath",
            "sudo cd $installPath && curl -sS https://getcomposer.org/installer | php",
            "sudo mv $installPath/composer.phar $installPath/$binaryName"
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

        $result = Composer::install($installPath, $binaryName, true);
        $this->assertEquals("$installPath/$binaryName", $result);
    }

    public function testInstallWithoutSudo(): void
    {
        $installPath = '/usr/local/bin';
        $binaryName = 'composer';
        $expectedCommands = [
            "mkdir -p $installPath",
            "cd $installPath && curl -sS https://getcomposer.org/installer | php",
            "mv $installPath/composer.phar $installPath/$binaryName"
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

        $result = Composer::install($installPath, $binaryName, false);
        $this->assertEquals("$installPath/$binaryName", $result);
    }

    public function testInstallWithDefaultBinaryName(): void
    {
        $installPath = '/usr/local/bin';
        $expectedCommands = [
            "mkdir -p $installPath",
            "cd $installPath && curl -sS https://getcomposer.org/installer | php"
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

        $result = Composer::install($installPath);
        $this->assertEquals("$installPath/composer.phar", $result);
    }

    public function testRunCommandWithVerbosity(): void
    {
        $path = '/var/www/html';
        $command = 'require';
        $arguments = 'package/name:1.0.0';

        // Test with verbose output
        $this->outputMock->method('isVerbose')->willReturn(true);
        $this->outputMock->method('isVeryVerbose')->willReturn(false);
        $this->outputMock->method('isDebug')->willReturn(false);

        $expectedCommand = "cd $path && composer require package/name:1.0.0 -v";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('Composer output');

        $result = Composer::runCommand($path, $command, $arguments);
        $this->assertEquals('Composer output', $result);
    }

    public function testRunCommandWithVeryVerboseOutput(): void
    {
        $path = '/var/www/html';
        $command = 'require';
        $arguments = 'package/name:1.0.0';

        // Test with very verbose output
        $this->outputMock->method('isVerbose')->willReturn(false);
        $this->outputMock->method('isVeryVerbose')->willReturn(true);
        $this->outputMock->method('isDebug')->willReturn(false);

        $expectedCommand = "cd $path && composer require package/name:1.0.0 -vv";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('Composer output');

        $result = Composer::runCommand($path, $command, $arguments);
        $this->assertEquals('Composer output', $result);
    }

    public function testRunCommandWithDebugOutput(): void
    {
        $path = '/var/www/html';
        $command = 'require';
        $arguments = 'package/name:1.0.0';

        // Test with debug output
        $this->outputMock->method('isVerbose')->willReturn(false);
        $this->outputMock->method('isVeryVerbose')->willReturn(false);
        $this->outputMock->method('isDebug')->willReturn(true);

        $expectedCommand = "cd $path && composer require package/name:1.0.0 -vvv";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('Composer output');

        $result = Composer::runCommand($path, $command, $arguments);
        $this->assertEquals('Composer output', $result);
    }
} 
