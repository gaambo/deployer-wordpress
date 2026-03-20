<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Component\Ssh\Client;
use Gaambo\DeployerWordpress\Files;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;

class FilesIntegrationTest extends IntegrationTestCase
{
    private MockObject $processRunnerMock;
    private MockObject $sshClientMock;
    private MockObject $outputMock;
    private MockObject $rsyncMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the processRunner and sshClient
        $this->processRunnerMock = $this->createMock(ProcessRunner::class);
        $this->sshClientMock = $this->createMock(Client::class);
        $this->outputMock = $this->createMock(OutputInterface::class);
        $this->rsyncMock = $this->createMock(\Deployer\Utility\Rsync::class);

        // Set them in the Deployer container
        $this->deployer['processRunner'] = $this->processRunnerMock;
        $this->deployer['sshClient'] = $this->sshClientMock;
        $this->deployer['output'] = $this->outputMock;
        $this->deployer['rsync'] = $this->rsyncMock;
        
        // Set default configuration
        $this->host->set('current_path', '/var/www/current');
        $this->host->set('release_or_current_path', '/var/www/current');
        $this->host->set('zip_options', '--exclude=*.zip');
    }

    public function testPushFiles(): void
    {
        $localPath = 'wp-content/themes/my-theme';
        $remotePath = 'wp-content/themes';
        $rsyncOptions = ['--exclude=*.log'];

        // Set up expectations for upload
        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/var/www/current/wp-content/themes/my-theme/',
                '/var/www/current/wp-content/themes/',
                ['options' => $rsyncOptions]
            );

        Files::pushFiles($localPath, $remotePath, $rsyncOptions);
    }

    public function testPullFiles(): void
    {
        $remotePath = 'wp-content/uploads';
        $localPath = 'wp-content/uploads';
        $rsyncOptions = ['--exclude=*.tmp'];

        // Set up expectations for download
        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/var/www/current/wp-content/uploads/',
                '/var/www/current/wp-content/uploads/',
                ['options' => $rsyncOptions]
            );

        Files::pullFiles($remotePath, $localPath, $rsyncOptions);
    }

    public function testZipFilesWithTrailingSlash(): void
    {
        $dir = '/var/www/current/wp-content/uploads/';
        $backupDir = '/var/www/backups';
        $filename = 'uploads';

        // Set up expectations for zip command
        $this->processRunnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($backupDir, $filename) {
                static $callNumber = 0;
                $callNumber++;

                switch ($callNumber) {
                    case 1:
                        $this->assertEquals("mkdir -p $backupDir", $command);
                        return 'Mkdir output';
                    case 2:
                        $this->assertStringContainsString("cd /var/www/current/wp-content/uploads/ && zip -r ", $command);
                        $this->assertStringContainsString(" . --exclude=*.zip && mv ", $command);
                        $this->assertStringContainsString(" $backupDir/", $command);
                        return 'Zip output';
                }
            });

        $result = Files::zipFiles($dir, $backupDir, $filename);
        $this->assertStringContainsString("$backupDir/{$filename}_", $result);
        $this->assertStringEndsWith('.zip', $result);
    }

    public function testZipFilesWithoutTrailingSlash(): void
    {
        $dir = '/var/www/current/wp-content/uploads';
        $backupDir = '/var/www/backups';
        $filename = 'uploads';

        // Set up expectations for zip command
        $this->processRunnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturnCallback(function ($host, $command) use ($backupDir, $filename) {
                static $callNumber = 0;
                $callNumber++;

                switch ($callNumber) {
                    case 1:
                        $this->assertEquals("mkdir -p $backupDir", $command);
                        return 'Mkdir output';
                    case 2:
                        $this->assertStringContainsString("cd /var/www/current/wp-content && zip -r ", $command);
                        $this->assertStringContainsString(" uploads --exclude=*.zip && mv ", $command);
                        $this->assertStringContainsString(" $backupDir/", $command);
                        return 'Zip output';
                }
            });

        $result = Files::zipFiles($dir, $backupDir, $filename);
        $this->assertStringContainsString("$backupDir/{$filename}_", $result);
        $this->assertStringEndsWith('.zip', $result);
    }
} 