<?php

namespace Gaambo\DeployerWordpress\Tests\Functional\Tasks;

use Gaambo\DeployerWordpress\Tests\Functional\FunctionalTestCase;

class MuPluginsTasksFunctionalTest extends FunctionalTestCase
{
    private string $localMuPluginsDir;
    private string $remoteMuPluginsDir;
    private string $localBackupDir;
    private string $remoteBackupDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up temporary directories
        $this->localMuPluginsDir = $this->localDocRootDir . '/wp-content/mu-plugins';
        $this->remoteMuPluginsDir = $this->remoteReleaseDir . '/wp-content/mu-plugins';
        $this->localBackupDir = $this->localDir . '/backups';
        $this->remoteBackupDir = $this->remoteDir . '/backups';

        // Create directories
        mkdir($this->localMuPluginsDir, 0755, true);
        mkdir($this->remoteMuPluginsDir, 0755, true);
        mkdir($this->localBackupDir, 0755, true);
        mkdir($this->remoteBackupDir, 0755, true);

        // Configure paths in deployer
        $this->deployer->config->set('mu-plugins/dir', 'wp-content/mu-plugins');
        $this->deployer->config->set('mu-plugin/name', 'test-plugin');
        $this->deployer->config->set('backup_path', $this->remoteBackupDir);

        // Configure localhost
        $this->localHost->set('backup_path', $this->localBackupDir);
    }

    public function testListAvailableTasks(): void
    {
        $this->dep('list', null);
        $output = $this->tester->getDisplay();

        $this->assertStringContainsString('mu-plugins:push', $output);
        $this->assertStringContainsString('mu-plugins:pull', $output);
        $this->assertStringContainsString('mu-plugins:backup:remote', $output);
        $this->assertStringContainsString('mu-plugins:backup:local', $output);
        $this->assertStringContainsString('mu-plugin:vendors', $output);
    }

    public function testMuPluginsPush(): void
    {
        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Run push task
        $result = $this->dep('mu-plugins:push');
        $this->assertEquals(0, $result);

        // Verify plugin directory was pushed correctly
        $this->assertDirectoryExists($this->remoteMuPluginsDir . '/test-plugin');
        $this->assertFileExists($this->remoteMuPluginsDir . '/test-plugin/test-plugin.php');
        $this->assertFileExists($this->remoteMuPluginsDir . '/test-plugin/composer.json');
    }

    public function testMuPluginsPushWithCustomFilter(): void
    {
        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $this->deployer->config->set('mu-plugins/filter', ['- *.json']);

        $result = $this->dep('mu-plugins:push');
        $this->assertEquals(0, $result);

        // Verify only PHP files were pushed
        $this->assertDirectoryExists($this->remoteMuPluginsDir . '/test-plugin');
        $this->assertFileExists($this->remoteMuPluginsDir . '/test-plugin/test-plugin.php');
        $this->assertFileDoesNotExist($this->remoteMuPluginsDir . '/test-plugin/composer.json');
    }

    public function testMuPluginsPull(): void
    {
        // Set up remote directory structure
        $remotePluginDir = $this->remoteMuPluginsDir . '/test-plugin';
        mkdir($remotePluginDir, 0755, true);

        // Copy from fixtures to remote
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $this->copyDirectory($fixturePluginDir, $remotePluginDir);

        // Run pull task
        $result = $this->dep('mu-plugins:pull');
        $this->assertEquals(0, $result);

        // Verify plugin directory was pulled correctly
        $this->assertDirectoryExists($this->localMuPluginsDir . '/test-plugin');
        $this->assertFileExists($this->localMuPluginsDir . '/test-plugin/test-plugin.php');
        $this->assertFileExists($this->localMuPluginsDir . '/test-plugin/composer.json');
    }

    public function testMuPluginsPullWithCustomFilter(): void
    {
        $this->deployer->config->set('mu-plugins/filter', ['- *.json']);

        // Set up remote directory structure
        $remotePluginDir = $this->remoteMuPluginsDir . '/test-plugin';
        mkdir($remotePluginDir, 0755, true);

        // Copy from fixtures to remote
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $this->copyDirectory($fixturePluginDir, $remotePluginDir);

        // Run pull task
        $result = $this->dep('mu-plugins:pull');
        $this->assertEquals(0, $result);

        // Verify only PHP files were pulled
        $this->assertDirectoryExists($this->localMuPluginsDir . '/test-plugin');
        $this->assertFileExists($this->localMuPluginsDir . '/test-plugin/test-plugin.php');
        $this->assertFileDoesNotExist($this->localMuPluginsDir . '/test-plugin/composer.json');
    }

    public function testMuPluginsBackupRemote(): void
    {
        // Copy from fixtures to remote first
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->remoteMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Run backup task
        $result = $this->dep('mu-plugins:backup:remote');
        $this->assertEquals(0, $result);

        // Check if backup file exists locally
        $backupFiles = glob($this->localBackupDir . '/backup_mu-plugins_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist');

        // Extract backup to verify contents
        $backupFile = $backupFiles[0];
        $extractDir = $this->localBackupDir . '/extracted';
        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($backupFile));
        $zip->extractTo($extractDir);
        $zip->close();

        // Verify plugin files are in the backup
        $this->assertDirectoryExists($extractDir . '/test-plugin');
        $this->assertFileExists($extractDir . '/test-plugin/test-plugin.php');
        $this->assertFileExists($extractDir . '/test-plugin/composer.json');

        // Cleanup
        $this->removeDirectory($extractDir);
    }

    public function testMuPluginsBackupRemoteWithCustomBackupPath(): void
    {
        // Set custom backup path
        $customBackupPath = $this->remoteDir . '/custom_backups';
        mkdir($customBackupPath, 0755, true);
        $this->deployer->config->set('backup_path', $customBackupPath);
        $this->localHost->set('backup_path', $this->localDir . '/custom_backups');
        mkdir($this->localDir . '/custom_backups', 0755, true);

        // Copy from fixtures to remote first
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->remoteMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Run backup task
        $result = $this->dep('mu-plugins:backup:remote');
        $this->assertEquals(0, $result);

        // Check if backup file exists in custom location
        $backupFiles = glob($this->localDir . '/custom_backups/backup_mu-plugins_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist in custom location');

        // Extract backup to verify contents
        $backupFile = $backupFiles[0];
        $extractDir = $this->localDir . '/custom_backups/extracted';
        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($backupFile));
        $zip->extractTo($extractDir);
        $zip->close();

        // Verify plugin files are in the backup
        $this->assertDirectoryExists($extractDir . '/test-plugin');
        $this->assertFileExists($extractDir . '/test-plugin/test-plugin.php');
        $this->assertFileExists($extractDir . '/test-plugin/composer.json');

        // Cleanup
        $this->removeDirectory($extractDir);
    }

    public function testMuPluginsBackupLocal(): void
    {
        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Run backup task
        $result = $this->dep('mu-plugins:backup:local');
        $this->assertEquals(0, $result);

        // Check if backup file exists
        $backupFiles = glob($this->localBackupDir . '/backup_mu-plugins_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist');

        // Extract backup to verify contents
        $backupFile = $backupFiles[0];
        $extractDir = $this->localBackupDir . '/extracted';
        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($backupFile));
        $zip->extractTo($extractDir);
        $zip->close();

        // Verify plugin files are in the backup
        $this->assertDirectoryExists($extractDir . '/test-plugin');
        $this->assertFileExists($extractDir . '/test-plugin/test-plugin.php');
        $this->assertFileExists($extractDir . '/test-plugin/composer.json');

        // Cleanup
        $this->removeDirectory($extractDir);
    }

    public function testMuPluginsBackupLocalWithCustomBackupPath(): void
    {
        // Set custom backup path
        $customBackupPath = $this->localDir . '/custom_backups';
        mkdir($customBackupPath, 0755, true);
        $this->localHost->set('backup_path', $customBackupPath);

        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Run backup task
        $result = $this->dep('mu-plugins:backup:local');
        $this->assertEquals(0, $result);

        // Check if backup file exists in custom location
        $backupFiles = glob($customBackupPath . '/backup_mu-plugins_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist in custom location');

        // Extract backup to verify contents
        $backupFile = $backupFiles[0];
        $extractDir = $customBackupPath . '/extracted';
        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($backupFile));
        $zip->extractTo($extractDir);
        $zip->close();

        // Verify plugin files are in the backup
        $this->assertDirectoryExists($extractDir . '/test-plugin');
        $this->assertFileExists($extractDir . '/test-plugin/test-plugin.php');
        $this->assertFileExists($extractDir . '/test-plugin/composer.json');

        // Cleanup
        $this->removeDirectory($extractDir);
    }

    public function testMuPluginVendors(): void
    {
        // Set default configuration
        $this->deployer->config->set('mu-plugin/name', 'test-plugin');
        $this->deployer->config->set('composer_action', 'install');
        $this->deployer->config->set('composer_options', '--no-dev --no-interaction');
        $this->deployer->config->set('bin/composer', 'composer');

        // Copy directory plugin from fixtures to target location
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->remoteMuPluginsDir . '/test-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Mock composer command execution
        $this->mockCommands([
            'cd ' . $this->remoteReleaseDir . '/wp-content/mu-plugins/test-plugin && composer install --no-dev --no-interaction -v' => function () {
                // Create composer.lock file with empty JSON object
                $lockFile = $this->remoteReleaseDir . '/wp-content/mu-plugins/test-plugin/composer.lock';
                file_put_contents($lockFile, '{}');
                return 'Composer dependencies installed successfully';
            }
        ]);

        // Run the vendors task
        $result = $this->dep('mu-plugin:vendors');

        // Verify task execution
        $this->assertEquals(0, $result);
        $this->assertFileExists($this->remoteReleaseDir . '/wp-content/mu-plugins/test-plugin/composer.lock');
    }

    public function testMuPluginVendorsWithCustomPaths(): void
    {
        // Set custom configuration
        $this->deployer->config->set('mu-plugins/dir', 'wp-content/custom-mu-plugins');
        $this->deployer->config->set('mu-plugin/name', 'my-custom-plugin');
        $this->deployer->config->set('composer_action', 'install');
        $this->deployer->config->set('composer_options', '--no-dev --no-interaction');
        $this->deployer->config->set('bin/composer', 'composer');

        // Copy directory plugin from fixtures to custom location
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');
        $targetPluginDir = $this->remoteMuPluginsDir . '/../custom-mu-plugins/my-custom-plugin';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Mock composer command execution
        $expectedCommand = 'cd ' . $this->remoteReleaseDir . '/wp-content/custom-mu-plugins/my-custom-plugin && composer install --no-dev --no-interaction -v';
        $this->mockCommands([
            $expectedCommand => function () {
                // Create composer.lock file with empty JSON object
                $lockFile = $this->remoteReleaseDir . '/wp-content/custom-mu-plugins/my-custom-plugin/composer.lock';
                file_put_contents($lockFile, '{}');
                return 'Composer dependencies installed successfully';
            }
        ]);

        // Run the vendors task
        $result = $this->dep('mu-plugin:vendors');

        // Verify task execution
        $this->assertEquals(0, $result);
        $this->assertFileExists($this->remoteReleaseDir . '/wp-content/custom-mu-plugins/my-custom-plugin/composer.lock');
    }
}
