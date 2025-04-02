<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Component\Ssh\Client;
use Deployer\Exception\ConfigurationException;
use Gaambo\DeployerWordpress\NPM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;

class NPMIntegrationTest extends IntegrationTestCase
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

        // Set default npm configuration
        $this->deployer->config->set('bin/npm', 'npm');
    }

    public function testRunScript(): void
    {
        $path = '/var/www/html';
        $script = 'build';
        $arguments = '--env=production';
        $expectedCommand = "cd $path && npm run-script $script $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runScript($path, $script, $arguments);
        $this->assertEquals('NPM output', $result);
        $this->addToAssertionCount(1); // Count the mock expectation as an assertion
    }

    public function testRunCommand(): void
    {
        $path = '/var/www/html';
        $action = 'install';
        $arguments = '--save-dev package-name';
        $expectedCommand = "cd $path && npm $action $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runCommand($path, $action, $arguments);
        $this->assertEquals('NPM output', $result);
        $this->addToAssertionCount(1); // Count the mock expectation as an assertion
    }

    public function testRunInstall(): void
    {
        $path = '/var/www/html';
        $arguments = '--save-dev';
        $expectedCommand = "cd $path && npm install $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runInstall($path, $arguments);
        $this->assertEquals('NPM output', $result);
        $this->addToAssertionCount(1); // Count the mock expectation as an assertion
    }

    /**
     * @dataProvider verbosityProvider
     */
    public function testRunCommandWithVerbosity(bool $isVerbose, bool $isVeryVerbose, bool $isDebug, string $verbosityFlag): void
    {
        $path = '/var/www/html';
        $action = 'install';
        $arguments = '--save-dev';

        $this->outputMock->method('isVerbose')->willReturn($isVerbose);
        $this->outputMock->method('isVeryVerbose')->willReturn($isVeryVerbose);
        $this->outputMock->method('isDebug')->willReturn($isDebug);

        $expectedCommand = "cd $path && npm $action $arguments $verbosityFlag";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runCommand($path, $action, $arguments);
        $this->assertEquals('NPM output', $result);
        $this->addToAssertionCount(1); // Count the mock expectation as an assertion
    }

    public static function verbosityProvider(): array
    {
        return [
            'verbose' => [true, false, false, '-d'],
            'very verbose' => [false, true, false, '-dd'],
            'debug' => [false, false, true, '-ddd'],
        ];
    }

    public function testRunCommandWithComplexArguments(): void
    {
        $path = '/var/www/html';
        $action = 'install';
        $arguments = '--save-dev "package@1.0.0" --save-exact --no-audit --legacy-peer-deps';
        $expectedCommand = "cd $path && npm $action $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runCommand($path, $action, $arguments);
        $this->assertEquals('NPM output', $result);
    }

    public function testRunCommandWithSpecialCharactersInPath(): void
    {
        $path = '/var/www/html with spaces';
        $action = 'install';
        $arguments = '--save-dev';
        $expectedCommand = "cd $path && npm $action $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runCommand($path, $action, $arguments);
        $this->assertEquals('NPM output', $result);
    }

    public function testRunCommandWithVeryLongPath(): void
    {
        // Create a path that's 255 characters long (common filesystem limit)
        $path = str_repeat('a', 200) . '/path/to/project';
        $action = 'install';
        $arguments = '--save-dev';
        $expectedCommand = "cd $path && npm $action $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runCommand($path, $action, $arguments);
        $this->assertEquals('NPM output', $result);
    }

    public function testRunCommandWithComplexScriptName(): void
    {
        $path = '/var/www/html';
        $script = 'build:production:minify';
        $arguments = '--env=prod';
        $expectedCommand = "cd $path && npm run-script $script $arguments";

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runScript($path, $script, $arguments);
        $this->assertEquals('NPM output', $result);
    }

    public function testRunCommandWithCustomNpmBinary(): void
    {
        $path = '/var/www/html';
        $action = 'install';
        $arguments = '--save-dev';
        $expectedCommand = "cd $path && /usr/local/bin/npm $action $arguments";

        // Set custom npm binary path
        $this->deployer->config->set('bin/npm', '/usr/local/bin/npm');

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->host, $expectedCommand)
            ->willReturn('NPM output');

        $result = NPM::runCommand($path, $action, $arguments);
        $this->assertEquals('NPM output', $result);
    }

    public function testRunCommandWithInvalidNpmBinary(): void
    {
        $path = '/var/www/html';
        $action = 'install';
        $arguments = '--save-dev';
        $expectedCommand = "cd $path && npm $action $arguments";

        // Set invalid npm binary (should fall back to default)
        $this->deployer->config->set('bin/npm', null);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Config option "bin/npm" does not exist');
        $result = NPM::runCommand($path, $action, $arguments);
    }
}
