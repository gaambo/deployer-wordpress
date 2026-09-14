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

    public function testPushFilesWithAbsolutePath(): void
    {
        $localPath = '/tmp/themes/my-theme';
        $remotePath = '/tmp/themes';
        $rsyncOptions = ['--exclude=*.log'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p /tmp/themes');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/tmp/themes/my-theme/',
                '/tmp/themes/',
                ['options' => $rsyncOptions]
            );

        Files::pushFiles($localPath, $remotePath, $rsyncOptions);
    }

    public function testPushFilesWithHomeRelativePath(): void
    {
        $localPath = '~/themes/my-theme';
        $remotePath = '~/themes';
        $rsyncOptions = ['--exclude=*.log'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p ~/themes');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '~/themes/my-theme/',
                '~/themes/',
                ['options' => $rsyncOptions]
            );

        Files::pushFiles($localPath, $remotePath, $rsyncOptions);
    }

    public function testPullFilesWithAbsolutePath(): void
    {
        $remotePath = '/tmp/uploads';
        $localPath = '/tmp/uploads';
        $rsyncOptions = ['--exclude=*.tmp'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p /tmp/uploads');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/tmp/uploads/',
                '/tmp/uploads/',
                ['options' => $rsyncOptions]
            );

        Files::pullFiles($remotePath, $localPath, $rsyncOptions);
    }

    public function testPullFilesWithHomeRelativePath(): void
    {
        $remotePath = '~/uploads';
        $localPath = '~/uploads';
        $rsyncOptions = ['--exclude=*.tmp'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p ~/uploads');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '~/uploads/',
                '~/uploads/',
                ['options' => $rsyncOptions]
            );

        Files::pullFiles($remotePath, $localPath, $rsyncOptions);
    }

    public function testPushFileWithRelativePath(): void
    {
        $localPath = 'data/db_dumps/db_backup.sql';
        $remotePath = 'data/db_dumps/db_backup.sql';
        $rsyncOptions = ['--checksum'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p /var/www/current/data/db_dumps');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/var/www/current/data/db_dumps/db_backup.sql',
                '/var/www/current/data/db_dumps/db_backup.sql',
                ['options' => $rsyncOptions]
            );

        Files::pushFile($localPath, $remotePath, $rsyncOptions);
    }

    public function testPushFileWithAbsolutePath(): void
    {
        $localPath = '/tmp/db_backup.sql';
        $remotePath = '/tmp/db_backup.sql';
        $rsyncOptions = ['--checksum'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p /tmp');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/tmp/db_backup.sql',
                '/tmp/db_backup.sql',
                ['options' => $rsyncOptions]
            );

        Files::pushFile($localPath, $remotePath, $rsyncOptions);
    }

    public function testPushFileWithHomeRelativePath(): void
    {
        $localPath = '~/data/db_dumps/db_backup.sql';
        $remotePath = '~/data/db_dumps/db_backup.sql';
        $rsyncOptions = ['--checksum'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p ~/data/db_dumps');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '~/data/db_dumps/db_backup.sql',
                '~/data/db_dumps/db_backup.sql',
                ['options' => $rsyncOptions]
            );

        Files::pushFile($localPath, $remotePath, $rsyncOptions);
    }

    public function testPullFileWithRelativePath(): void
    {
        $remotePath = 'data/db_dumps/db_backup.sql';
        $localPath = 'data/db_dumps/db_backup.sql';
        $rsyncOptions = ['--checksum'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p /var/www/current/data/db_dumps');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/var/www/current/data/db_dumps/db_backup.sql',
                '/var/www/current/data/db_dumps/db_backup.sql',
                ['options' => $rsyncOptions]
            );

        Files::pullFile($remotePath, $localPath, $rsyncOptions);
    }

    public function testPullFileWithAbsolutePath(): void
    {
        $remotePath = '/tmp/db_backup.sql';
        $localPath = '/tmp/db_backup.sql';
        $rsyncOptions = ['--checksum'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p /tmp');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '/tmp/db_backup.sql',
                '/tmp/db_backup.sql',
                ['options' => $rsyncOptions]
            );

        Files::pullFile($remotePath, $localPath, $rsyncOptions);
    }

    public function testPullFileWithHomeRelativePath(): void
    {
        $remotePath = '~/data/db_dumps/db_backup.sql';
        $localPath = '~/data/db_dumps/db_backup.sql';
        $rsyncOptions = ['--checksum'];

        $this->processRunnerMock
            ->expects($this->once())
            ->method('run')
            ->with($this->anything(), 'mkdir -p ~/data/db_dumps');

        $this->rsyncMock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->host,
                '~/data/db_dumps/db_backup.sql',
                '~/data/db_dumps/db_backup.sql',
                ['options' => $rsyncOptions]
            );

        Files::pullFile($remotePath, $localPath, $rsyncOptions);
    }

    public function testResolvePath(): void
    {
        $basePath = '/var/www/current';

        $this->assertEquals(
            '/var/www/current/data/db_dumps',
            Files::resolvePath('data/db_dumps', $basePath)
        );
        $this->assertEquals(
            '/var/www/current/data/db_dumps/',
            Files::resolvePath('data/db_dumps/', $basePath)
        );
        $this->assertEquals(
            '/tmp/db_dumps',
            Files::resolvePath('/tmp/db_dumps', $basePath)
        );
        $this->assertEquals(
            '~/db_dumps',
            Files::resolvePath('~/db_dumps', $basePath)
        );
        $this->assertEquals(
            '/var/www/current/',
            Files::resolvePath('', $basePath)
        );
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
