<?php

namespace Gaambo\DeployerWordpress\Tests\Functional;

use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Component\Ssh\Client;
use Deployer\Deployer;
use Deployer\Executor\Server;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Utility\Rsync;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Base class for functional tests that need to test Deployer tasks.
 *
 * This class provides:
 * 1. A temporary test environment with local and remote directories
 * 2. Configured local and remote hosts
 * 3. Mocked SSH client and process runner for testing commands
 * 4. A LocalRsync implementation for testing file transfers
 */
abstract class FunctionalTestCase extends TestCase
{
    protected const RECIPE_PATH = __DIR__ . '/../Fixtures/recipes/common.php';
    protected Deployer $deployer;
    protected ApplicationTester $tester;

    // Test environment paths
    protected Host $localHost;
    protected Host $remoteHost;
    protected string $testDir;
    protected string $localDir;
    protected string $localReleaseDir;
    protected string $remoteDir;
    protected string $remoteReleaseDir;

    // Mocked services
    protected Client|MockObject $sshClient;
    protected ProcessRunner $originalProcessRunner;

    // If a child test needs to mock rsync to simulate a failed download, use this.
    protected ProcessRunner|MockObject $mockedRunner;
    protected Rsync|MockObject|null $rsyncMock = null;

    protected function setUp(): void
    {
        parent::setUp();
        // 1. Set up test environment directories
        $this->createTestDirectories();

        // 2. Initialize Deployer and Application
        $console = new Application();
        $console->setAutoExit(false);
        $this->tester = new ApplicationTester($console);
        $this->deployer = new Deployer($console);
        $this->deployer->importer->import(static::RECIPE_PATH);
        $this->deployer->init();

        // 3. Configure hosts
        $this->setUpHosts();

        // 4. Set up mocked services
        $this->setUpMockedServices();
    }

    /**
     * Creates a unique test directory structure for this test run
     */
    private function createTestDirectories(): void
    {
        $this->testDir = __TEMP_DIR__ . '/' . uniqid();

        // These are the deploy_path.
        $this->localDir = $this->testDir . '/local';
        $this->remoteDir = $this->testDir . '/remote';

        // Fixed directory, no symlinks. Just like in example deploy.yml
        $this->localReleaseDir = $this->localDir . '/public_html';
        $this->remoteReleaseDir = $this->remoteDir . '/public_html';

        mkdir($this->localDir, 0755, true);
        mkdir($this->localReleaseDir, 0755, true);
        mkdir($this->remoteDir, 0755, true);
        mkdir($this->remoteReleaseDir, 0755, true);
    }

    /**
     * Sets up local and remote hosts with their paths
     */
    private function setUpHosts(): void
    {
        // Local host setup
        // This mirrors the setup in examples/simple/deploy.php
        $this->localHost = new Localhost();
        $this->localHost->set('deploy_path', $this->localDir);
        $this->localHost->set('release_path', $this->localReleaseDir);
        $this->localHost->set('dbdump/path', $this->localDir . '/data/db_dumps');
        $this->localHost->set('backup_path', $this->localDir . '/data/backups');
        $this->localHost->set('bin/wp', 'wp');
        $this->localHost->set('bin/php', 'php');

        // Remote host setup (using Localhost for testing so rsync runs on the same host)
        $this->remoteHost = new Localhost('testremote');
        $this->remoteHost->set('deploy_path', $this->remoteDir);
        $this->remoteHost->set('release_path', $this->remoteReleaseDir);
        $this->remoteHost->set('dbdump/path', $this->remoteDir . '/data/db_dumps');
        $this->remoteHost->set('backup_path', $this->remoteDir . '/data/backups');
        $this->remoteHost->set('bin/wp', 'wp');
        $this->remoteHost->set('bin/php', 'php');

        // Register hosts with Deployer
        $this->deployer->hosts->set('localhost', $this->localHost);
        $this->deployer->hosts->set('testremote', $this->remoteHost);
    }

    /**
     * Creates mock objects for SSH and process running
     * And register services in Deployer container
     */
    protected function setUpMockedServices(): void
    {
        $this->sshClient = $this->createMock(Client::class);
        $this->deployer['sshClient'] = $this->sshClient;
        // Server is not needed and throws errors in dev.
        $this->deployer['server'] = $this->createMock(Server::class);

        // Create a mock runner but don't use it by default.
        // Can't store originalProcessRunner here already, because it needs to dynamically resolve from the container later.
        $this->mockedRunner = $this->createMock(ProcessRunner::class);
    }

    /**
     * Helper to mock specific commands while passing others through
     *
     * @param array<string, callable|int> $commandsToMock Map of command patterns to callables or return values
     */
    protected function mockCommands(array $commandsToMock): void
    {
        // Set up the mock to handle specific commands
        $this->mockedRunner->expects($this->any())
            ->method('run')
            ->willReturnCallback(function ($host, $command, $options = []) use ($commandsToMock) {
                // Check if this command should be mocked
                foreach ($commandsToMock as $pattern => $handler) {
                    if (str_contains($command, $pattern)) {
                        return is_callable($handler) ? $handler($host, $command, $options) : $handler;
                    }
                }

                // If not mocked, pass through to original runner
                return $this->originalProcessRunner->run($host, $command, $options);
            });

        // Use a closure factory, because the original runner needs dependencies.
        $this->deployer['processRunner'] = function ($c) {
            $this->originalProcessRunner = new ProcessRunner($c['pop'], $c['logger']);
            return $this->mockedRunner;
        };
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
        parent::tearDown();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Returns the full path to a fixture file
     */
    protected function getFixturePath(string $path): string
    {
        return __FIXTURES__ . '/' . $path;
    }

    /**
     * Runs a deployer task with the given arguments
     */
    protected function dep(string $task, ?string $host = 'testremote', array $args = []): int
    {
        $input = [$task];
        if (!empty($host)) {
            $input['selector'] = [$host];
        }
        $input['--file'] = static::RECIPE_PATH;
        $input = array_merge($input, $args);
        return $this->tester->run($input, [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);
    }

    /**
     * Helper method to mock rsync failures. Use $checkSource and $checkDestination if you want to mock only specific up-/download.
     *
     * @param string|null $checkSource When set, the source in the download call will be compared against and only these downloads will fail.
     * @param string|null $checkDestination When set, the destination in the download call will be compared against and only these downloads will fail.
     */
    protected function mockRsyncFailure(?string $checkSource = null, ?string $checkDestination = null): void
    {
        $this->rsyncMock = $this->createMock(Rsync::class);
        $this->deployer['rsync'] = $this->rsyncMock;
        $this->rsyncMock->expects($this->once())
            ->method('call')
            ->willReturnCallback(function (Host $host, $source, $destination, $options) use ($checkSource, $checkDestination) {
                if ($checkSource && $source !== $checkSource) {
                    return;
                }
                if ($checkDestination && $destination !== $checkSource) {
                    return;
                }
                throw new Exception('Download failed');
            });
    }
}
