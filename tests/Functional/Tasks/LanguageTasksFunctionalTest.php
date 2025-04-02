<?php

namespace Gaambo\DeployerWordpress\Tests\Functional\Tasks;

use Gaambo\DeployerWordpress\Tests\Functional\FunctionalTestCase;

class LanguageTasksFunctionalTest extends FunctionalTestCase
{
    private string $localLanguagesDir;
    private string $remoteLanguagesDir;
    private string $localBackupDir;
    private string $remoteBackupDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up temporary directories
        $this->localLanguagesDir = $this->localDocRootDir . '/wp-content/languages';
        $this->remoteLanguagesDir = $this->remoteReleaseDir . '/wp-content/languages';
        $this->localBackupDir = $this->localDir . '/backups';
        $this->remoteBackupDir = $this->remoteDir . '/backups';

        // Create directories
        mkdir($this->localLanguagesDir, 0755, true);
        mkdir($this->remoteLanguagesDir, 0755, true);
        mkdir($this->localBackupDir, 0755, true);
        mkdir($this->remoteBackupDir, 0755, true);

        // Configure paths in deployer
        // $this->deployer->config->set('languages/dir', 'wp-content/languages'); // Use default one set in recipe.
        $this->deployer->config->set('backup_path', $this->remoteBackupDir);

        // Configure localhost
        $this->localHost->set('backup_path', $this->localBackupDir);
    }

    public function testListAvailableTasks(): void
    {
        $this->dep('list', null);
        $output = $this->tester->getDisplay();

        $this->assertStringContainsString('languages:push', $output);
        $this->assertStringContainsString('languages:pull', $output);
        $this->assertStringContainsString('languages:backup:remote', $output);
        $this->assertStringContainsString('languages:backup:local', $output);
    }

    public function testLanguagesPush(): void
    {
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->localLanguagesDir . '/' . basename($languageFile));
        }

        $result = $this->dep('languages:push');
        $this->assertEquals(0, $result);

        foreach ($languageFiles as $languageFile) {
            $filePath = $this->remoteLanguagesDir . '/' . basename($languageFile);
            $originalFilePath = $this->localLanguagesDir . '/' . basename($languageFile);
            $this->assertFileExists($filePath);
            $this->assertEquals(
                file_get_contents($originalFilePath),
                file_get_contents($filePath)
            );
        }
    }

    public function testLanguagesPushWithCustomFilter(): void
    {
        $this->deployer->config->set('languages/filter', ['- *.po']);
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->localLanguagesDir . '/' . basename($languageFile));
        }

        $result = $this->dep('languages:push');
        $this->assertEquals(0, $result);

        $remoteLanguageFiles = glob($this->remoteLanguagesDir . '/*');
        $this->assertCount(3, $remoteLanguageFiles); // 4 - 1 .po file.
        foreach ($languageFiles as $languageFile) {
            if (str_ends_with($languageFile, '.po')) {
                continue;
            }
            $filePath = $this->remoteLanguagesDir . '/' . basename($languageFile);
            $originalFilePath = $this->localLanguagesDir . '/' . basename($languageFile);
            $this->assertFileExists($filePath);
            $this->assertEquals(
                file_get_contents($originalFilePath),
                file_get_contents($filePath)
            );
        }
    }

    public function testLanguagesPull(): void
    {
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->remoteLanguagesDir . '/' . basename($languageFile));
        }

        $result = $this->dep('languages:pull');
        $this->assertEquals(0, $result);

        foreach ($languageFiles as $languageFile) {
            $filePath = $this->localLanguagesDir . '/' . basename($languageFile);
            $originalFilePath = $this->remoteLanguagesDir . '/' . basename($languageFile);
            $this->assertFileExists($filePath);
            $this->assertEquals(
                file_get_contents($originalFilePath),
                file_get_contents($filePath)
            );
        }
    }

    public function testLanguagesPullWithCustomFilter(): void
    {
        $this->deployer->config->set('languages/filter', ['- *.po']);
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->remoteLanguagesDir . '/' . basename($languageFile));
        }

        $result = $this->dep('languages:pull');
        $this->assertEquals(0, $result);

        $localLanguageFiles = glob($this->localLanguagesDir . '/*');
        $this->assertCount(3, $localLanguageFiles); // 4 - 1 .po file.
        foreach ($languageFiles as $languageFile) {
            if (str_ends_with($languageFile, '.po')) {
                continue;
            }
            $filePath = $this->localLanguagesDir . '/' . basename($languageFile);
            $originalFilePath = $this->remoteLanguagesDir . '/' . basename($languageFile);
            $this->assertFileExists($filePath);
            $this->assertEquals(
                file_get_contents($originalFilePath),
                file_get_contents($filePath)
            );
        }
    }

    public function testLanguagesBackupRemote(): void
    {
        // Copy language files to remote
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->remoteLanguagesDir . '/' . basename($languageFile));
        }

        // Run backup task
        $result = $this->dep('languages:backup:remote');
        $this->assertEquals(0, $result);

        // Check if backup file exists locally
        $backupFiles = glob($this->localBackupDir . '/backup_languages_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist');

        // Extract backup to verify contents
        $backupFile = $backupFiles[0];
        $extractDir = $this->localBackupDir . '/extracted';
        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($backupFile));
        $zip->extractTo($extractDir);
        $zip->close();

        // Verify all language files are in the backup
        foreach ($languageFiles as $languageFile) {
            $fileName = basename($languageFile);
            $this->assertFileExists($extractDir . '/' . $fileName);
            $this->assertEquals(
                file_get_contents($languageFile),
                file_get_contents($extractDir . '/' . $fileName)
            );
        }

        // Cleanup
        $this->removeDirectory($extractDir);
    }

    public function testLanguagesBackupRemoteWithCustomBackupPath(): void
    {
        // Set custom backup path
        $customBackupPath = $this->remoteDir . '/custom_backups';
        mkdir($customBackupPath, 0755, true);
        $this->deployer->config->set('backup_path', $customBackupPath);
        $this->localHost->set('backup_path', $this->localDir . '/custom_backups');
        mkdir($this->localDir . '/custom_backups', 0755, true);

        // Copy language files to remote
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->remoteLanguagesDir . '/' . basename($languageFile));
        }

        // Run backup task
        $result = $this->dep('languages:backup:remote');
        $this->assertEquals(0, $result);

        // Check if backup file exists in custom location
        $backupFiles = glob($this->localDir . '/custom_backups/backup_languages_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist in custom location');
    }

    public function testLanguagesBackupLocal(): void
    {
        // Copy language files to local
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->localLanguagesDir . '/' . basename($languageFile));
        }

        // Run backup task
        $result = $this->dep('languages:backup:local');
        $this->assertEquals(0, $result);

        // Check if backup file exists locally
        $backupFiles = glob($this->localBackupDir . '/backup_languages_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist');

        // Extract backup to verify contents
        $backupFile = $backupFiles[0];
        $extractDir = $this->localBackupDir . '/extracted';
        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($backupFile));
        $zip->extractTo($extractDir);
        $zip->close();

        // Verify all language files are in the backup
        foreach ($languageFiles as $languageFile) {
            $fileName = basename($languageFile);
            $this->assertFileExists($extractDir . '/' . $fileName);
            $this->assertEquals(
                file_get_contents($languageFile),
                file_get_contents($extractDir . '/' . $fileName)
            );
        }

        // Cleanup
        $this->removeDirectory($extractDir);
    }

    public function testLanguagesBackupLocalWithCustomBackupPath(): void
    {
        // Set custom backup path
        $customBackupPath = $this->localDir . '/custom_backups';
        mkdir($customBackupPath, 0755, true);
        $this->localHost->set('backup_path', $customBackupPath);

        // Copy language files to local
        $languageFiles = glob($this->getFixturePath('languages/*'));
        foreach ($languageFiles as $languageFile) {
            copy($languageFile, $this->localLanguagesDir . '/' . basename($languageFile));
        }

        // Run backup task
        $result = $this->dep('languages:backup:local');
        $this->assertEquals(0, $result);

        // Check if backup file exists in custom location
        $backupFiles = glob($customBackupPath . '/backup_languages_*.zip');
        $this->assertCount(1, $backupFiles, 'Backup file should exist in custom location');
    }
}
