<?php

namespace Gaambo\DeployerWordpress\Tests\Functional\Tasks;

use Gaambo\DeployerWordpress\Tests\Functional\FunctionalTestCase;
use RuntimeException;

use function Deployer\set;

class DatabaseTasksFunctionalTest extends FunctionalTestCase
{
    public function testListAvailableTasks(): void
    {
        $this->dep('list', null);
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('db:remote:backup', $output, 'Should list db:remote:backup task');
        $this->assertStringContainsString('db:local:backup', $output, 'Should list db:local:backup task');
    }

    public function testDbRemoteBackup(): void
    {
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:remote:backup');
        $this->assertEquals(0, $result);

        // Verify dump files were created
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created');

        // Verify file contents match fixture
        $fixtureContent = file_get_contents($this->getFixturePath('database/dump.sql'));
        $remoteContent = file_get_contents($remoteDumpFiles[0]);
        $localContent = file_get_contents($localDumpFiles[0]);

        $this->assertEquals($fixtureContent, $remoteContent, 'Remote dump file should match fixture');
        $this->assertEquals($fixtureContent, $localContent, 'Local dump file should match fixture');
    }

    /**
     * Helper method to mock successful WP-CLI database export
     */
    protected function mockSuccessfulDbExport(string $wpBinary = 'wp'): void
    {
        $this->mockCommands([
            "$wpBinary db export" => function ($host, $command) use ($wpBinary) {
                if (preg_match('/db export (.*?db_backup-\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.sql)(?:\s|$)/', $command, $matches)) {
                    $dumpFile = $matches[1];
                } else {
                    // Determine if this is a local or remote command based on the host
                    $dumpFile = $host->getHostname() === 'local'
                        ? $this->localDir . '/dumps/db_backup_' . date('Y-m-d_H-i') . '.sql'
                        : $this->remoteDir . '/dumps/db_backup_' . date('Y-m-d_H-i') . '.sql';
                }
                copy($this->getFixturePath('database/dump.sql'), $dumpFile);
                return 'Database exported successfully';
            }
        ]);
    }

    public function testDbRemoteBackupWithCustomDumpPath(): void
    {
        // Set custom dump paths
        $customRemotePath = $this->remoteDir . '/custom/dumps';
        $customLocalPath = $this->localDir . '/custom/dumps';

        $this->remoteHost->set('dbdump/path', $customRemotePath);
        $this->localHost->set('dbdump/path', $customLocalPath);

        // Create custom dump directories
        mkdir($customRemotePath, 0755, true);
        mkdir($customLocalPath, 0755, true);

        // Mock successful WP-CLI export
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:remote:backup');
        $this->assertEquals(0, $result);

        // Verify dump file was created in custom remote directory
        $remoteDumpFiles = glob($customRemotePath . '/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created in custom path');

        // Verify dump file was downloaded to custom local directory
        $localDumpFiles = glob($customLocalPath . '/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created in custom path');

        // Verify file contents match fixture
        $fixtureContent = file_get_contents($this->getFixturePath('database/dump.sql'));
        $remoteContent = file_get_contents($remoteDumpFiles[0]);
        $localContent = file_get_contents($localDumpFiles[0]);

        $this->assertEquals($fixtureContent, $remoteContent, 'Remote dump file should match fixture');
        $this->assertEquals($fixtureContent, $localContent, 'Local dump file should match fixture');
    }

    public function testDbRemoteBackupWithCustomWpBinary(): void
    {
        // Set custom WP-CLI binary
        $this->remoteHost->set('bin/wp', '/usr/local/bin/wp-cli');
        $this->mockSuccessfulDbExport('/usr/local/bin/wp-cli');

        // Run the backup task
        $result = $this->dep('db:remote:backup');
        $this->assertEquals(0, $result);

        // Verify dump files were created
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created');
    }

    public function testDbRemoteBackupWithExistingDumpDirectory(): void
    {
        // Create dump directory with existing files
        $existingFile = $this->remoteDir . '/dumps/existing.sql';
        file_put_contents($existingFile, 'existing content');

        // Mock successful WP-CLI export
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:remote:backup');
        $this->assertEquals(0, $result);

        // Verify existing file wasn't modified
        $this->assertEquals('existing content', file_get_contents($existingFile), 'Existing file should not be modified');

        // Verify new dump file was created
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'New dump file should be created');
    }

    public function testDbRemoteBackupWithWpCliError(): void
    {
        $this->mockFailedDbExport();

        // Run the backup task and expect it to fail
        $result = $this->dep('db:remote:backup');
        $this->assertNotEquals(0, $result, 'Task should fail when WP-CLI fails');

        // Verify no dump files were created
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $this->assertCount(0, $remoteDumpFiles, 'No remote dump file should be created on error');
        $this->assertCount(0, $localDumpFiles, 'No local dump file should be created on error');
    }

    /**
     * Helper method to mock WP-CLI database export failure
     */
    protected function mockFailedDbExport(string $error = 'WP-CLI error: Database connection failed'): void
    {
        $this->mockCommands([
            'wp db export' => function () use ($error) {
                throw new RuntimeException($error);
            }
        ]);
    }

    public function testDbRemoteBackupWithDownloadError(): void
    {
        // Mock successful export but failed download
        $this->mockSuccessfulDbExport();
        $this->mockRsyncFailure();

        // Run the backup task and expect it to fail
        $result = $this->dep('db:remote:backup');
        $this->assertNotEquals(0, $result, 'Task should fail when download fails');

        // Verify remote dump file was created but local wasn't
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created even if download fails');
        $this->assertCount(0, $localDumpFiles, 'No local dump file should be created on download error');
    }

    public function testDbRemoteBackupWithInvalidDumpPath(): void
    {
        // Set invalid dump path (non-writable directory)
        $invalidPath = '/root/invalid/path';
        $this->remoteHost->set('dbdump/path', $invalidPath);

        // Run the backup task and expect it to fail
        $this->mockSuccessfulDbExport();
        $result = $this->dep('db:remote:backup');
        $this->assertNotEquals(0, $result, 'Task should fail with invalid dump path');
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Task db:remote:backup failed', $output);

        // Verify no dump files were created
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $invalidPathFiles = glob('/root/invalid/path/db_backup-*.sql');
        $this->assertCount(0, $remoteDumpFiles, 'No remote dump file should be created with invalid path');
        $this->assertCount(0, $localDumpFiles, 'No local dump file should be created with invalid path');
        $this->assertCount(0, $invalidPathFiles, 'No root dump file should be created with invalid path');
    }

    public function testDbLocalBackup(): void
    {
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:local:backup');
        $this->assertEquals(0, $result);

        // Verify dump files were created
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created');

        // Verify file contents match fixture
        $fixtureContent = file_get_contents($this->getFixturePath('database/dump.sql'));
        $localContent = file_get_contents($localDumpFiles[0]);
        $remoteContent = file_get_contents($remoteDumpFiles[0]);

        $this->assertEquals($fixtureContent, $localContent, 'Local dump file should match fixture');
        $this->assertEquals($fixtureContent, $remoteContent, 'Remote dump file should match fixture');
    }

    public function testDbLocalBackupWithCustomDumpPath(): void
    {
        // Set custom dump paths
        $customRemotePath = $this->remoteDir . '/custom/dumps';
        $customLocalPath = $this->localDir . '/custom/dumps';

        $this->remoteHost->set('dbdump/path', $customRemotePath);
        $this->localHost->set('dbdump/path', $customLocalPath);

        // Create custom dump directories
        mkdir($customRemotePath, 0755, true);
        mkdir($customLocalPath, 0755, true);

        // Mock successful WP-CLI export
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:local:backup');
        $this->assertEquals(0, $result);

        // Verify dump file was created in custom local directory
        $localDumpFiles = glob($customLocalPath . '/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created in custom path');

        // Verify dump file was uploaded to custom remote directory
        $remoteDumpFiles = glob($customRemotePath . '/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created in custom path');

        // Verify file contents match fixture
        $fixtureContent = file_get_contents($this->getFixturePath('database/dump.sql'));
        $localContent = file_get_contents($localDumpFiles[0]);
        $remoteContent = file_get_contents($remoteDumpFiles[0]);

        $this->assertEquals($fixtureContent, $localContent, 'Local dump file should match fixture');
        $this->assertEquals($fixtureContent, $remoteContent, 'Remote dump file should match fixture');
    }

    public function testDbLocalBackupWithCustomWpBinary(): void
    {
        // Set custom WP-CLI binary
        $this->localHost->set('bin/wp', '/usr/local/bin/wp-cli');
        $this->mockSuccessfulDbExport('/usr/local/bin/wp-cli');

        // Run the backup task
        $result = $this->dep('db:local:backup');
        $this->assertEquals(0, $result);

        // Verify dump files were created
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created');
    }

    public function testDbLocalBackupWithExistingDumpDirectory(): void
    {
        // Create dump directory with existing files
        $existingFile = $this->remoteDir . '/dumps/existing.sql';
        file_put_contents($existingFile, 'existing content');

        // Mock successful WP-CLI export
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:local:backup');
        $this->assertEquals(0, $result);

        // Verify existing file wasn't modified
        $this->assertEquals('existing content', file_get_contents($existingFile), 'Existing file should not be modified');

        // Verify new dump file was created
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'New dump file should be created');
    }

    public function testDbLocalBackupWithWpCliError(): void
    {
        $this->mockFailedDbExport();

        // Run the backup task and expect it to fail
        $result = $this->dep('db:local:backup');
        $this->assertNotEquals(0, $result, 'Task should fail when WP-CLI fails');

        // Verify no dump files were created
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $this->assertCount(0, $localDumpFiles, 'No local dump file should be created on error');
        $this->assertCount(0, $remoteDumpFiles, 'No remote dump file should be created on error');
    }

    public function testDbLocalBackupWithUploadError(): void
    {
        // Mock successful export but failed upload
        $this->mockSuccessfulDbExport();
        $this->mockRsyncFailure();

        // Run the backup task and expect it to fail
        $result = $this->dep('db:local:backup');
        $this->assertNotEquals(0, $result, 'Task should fail when upload fails');

        // Verify local dump file was created but remote wasn't
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created even if upload fails');
        $this->assertCount(0, $remoteDumpFiles, 'No remote dump file should be created on upload error');
    }

    public function testDbLocalBackupWithInvalidDumpPath(): void
    {
        // Set invalid dump path (non-writable directory)
        $invalidPath = '/root/invalid/path';
        $this->localHost->set('dbdump/path', $invalidPath);

        // Run the backup task and expect it to fail
        $this->mockSuccessfulDbExport();
        $result = $this->dep('db:local:backup');
        $this->assertNotEquals(0, $result, 'Task should fail with invalid dump path');
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Task db:local:backup failed', $output);

        // Verify no dump files were created
        $localDumpFiles = glob($this->localDir . '/dumps/db_backup-*.sql');
        $remoteDumpFiles = glob($this->remoteDir . '/dumps/db_backup-*.sql');
        $invalidPathFiles = glob('/root/invalid/path/db_backup-*.sql');
        $this->assertCount(0, $localDumpFiles, 'No local dump file should be created with invalid path');
        $this->assertCount(0, $remoteDumpFiles, 'No remote dump file should be created with invalid path');
        $this->assertCount(0, $invalidPathFiles, 'No invalid path dump file should be created');
    }

    public function testDbRemoteImport(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful import and URL replacement
        $this->mockSuccessfulDbImport();

        // Run the import task
        $result = $this->dep('db:remote:import');
        $this->assertEquals(0, $result);

        // Verify dump file was cleaned up
        $this->assertFileDoesNotExist($dumpFile, 'Dump file should be removed after import');
    }

    /**
     * Helper method to mock successful database import and URL replacement
     */
    protected function mockSuccessfulDbImport(): void
    {
        $this->mockCommands([
            'wp db import' => function () {
                return 'Database imported successfully';
            },
            'wp search-replace' => function () {
                return 'Made some replacements';
            }
        ]);
    }

    public function testDbRemoteImportWithUploadsPathReplacement(): void
    {
        // Set up URLs and upload paths for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');
        $this->localHost->set('uploads/dir', '/local/uploads');
        $this->remoteHost->set('uploads/dir', '/remote/uploads');

        // Create a dump file to import
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful import and replacements
        $this->mockSuccessfulDbImport();

        // Run the import task
        $result = $this->dep('db:remote:import');
        $this->assertEquals(0, $result);

        // Verify dump file was cleaned up
        $this->assertFileDoesNotExist($dumpFile, 'Dump file should be removed after import');
    }

    public function testDbRemoteImportWithImportError(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock failed import
        $this->mockCommands([
            'wp db import' => function () {
                throw new RuntimeException('Import failed');
            }
        ]);

        // Run the import task and expect failure
        $result = $this->dep('db:remote:import');
        $this->assertNotEquals(0, $result, 'Task should fail when import fails');

        // Verify dump file still exists (not cleaned up on error)
        $this->assertFileExists($dumpFile, 'Dump file should remain when import fails');
    }

    public function testDbRemoteImportWithUrlReplaceError(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful import but failed URL replacement
        $this->mockCommands([
            'wp db import' => function () {
                return 'Database imported successfully';
            },
            'wp search-replace' => function () {
                throw new RuntimeException('URL replacement failed');
            }
        ]);

        // Run the import task and expect failure
        $result = $this->dep('db:remote:import');
        $this->assertNotEquals(0, $result, 'Task should fail when URL replacement fails');

        // Verify dump file still exists (not cleaned up on error)
        $this->assertFileExists($dumpFile, 'Dump file should remain when URL replacement fails');
    }

    public function testDbRemoteImportWithMissingDumpFile(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Set non-existent dump file path
        $dumpFile = $this->remoteDir . '/dumps/nonexistent.sql';
        set('dbdump/file', 'db_backup.sql');

        // Run the import task and expect failure
        $result = $this->dep('db:remote:import');
        $this->assertNotEquals(0, $result, 'Task should fail when dump file is missing');
    }

    public function testDbLocalImport(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful import and URL replacement
        $this->mockSuccessfulDbImport();

        // Run the import task
        $result = $this->dep('db:local:import');
        $this->assertEquals(0, $result);

        // Verify dump file was cleaned up
        $this->assertFileDoesNotExist($dumpFile, 'Dump file should be removed after import');
    }

    public function testDbLocalImportWithUploadsPathReplacement(): void
    {
        // Set up URLs and upload paths for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');
        $this->localHost->set('uploads/dir', '/local/uploads');
        $this->remoteHost->set('uploads/dir', '/remote/uploads');

        // Create a dump file to import
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful import and replacements
        $this->mockSuccessfulDbImport();

        // Run the import task
        $result = $this->dep('db:local:import');
        $this->assertEquals(0, $result);

        // Verify dump file was cleaned up
        $this->assertFileDoesNotExist($dumpFile, 'Dump file should be removed after import');
    }

    public function testDbLocalImportWithImportError(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock failed import
        $this->mockCommands([
            'wp db import' => function () {
                throw new RuntimeException('Import failed');
            }
        ]);

        // Run the import task and expect failure
        $result = $this->dep('db:local:import');
        $this->assertNotEquals(0, $result, 'Task should fail when import fails');

        // Verify dump file still exists (not cleaned up on error)
        $this->assertFileExists($dumpFile, 'Dump file should remain when import fails');
    }

    public function testDbLocalImportWithUrlReplaceError(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful import but failed URL replacement
        $this->mockCommands([
            'wp db import' => function () {
                return 'Database imported successfully';
            },
            'wp search-replace' => function () {
                throw new RuntimeException('URL replacement failed');
            }
        ]);

        // Run the import task and expect failure
        $result = $this->dep('db:local:import');
        $this->assertNotEquals(0, $result, 'Task should fail when URL replacement fails');

        // Verify dump file still exists (not cleaned up on error)
        $this->assertFileExists($dumpFile, 'Dump file should remain when URL replacement fails');
    }

    public function testDbLocalImportWithMissingDumpFile(): void
    {
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Set non-existent dump file path
        $dumpFile = $this->localDir . '/dumps/nonexistent.sql';
        set('dbdump/file', 'db_backup.sql');

        // Run the import task and expect failure
        $result = $this->dep('db:local:import');
        $this->assertNotEquals(0, $result, 'Task should fail when dump file is missing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set up required configuration
        $this->localHost->set('dbdump/path', $this->localDir . '/dumps');
        $this->remoteHost->set('dbdump/path', $this->remoteDir . '/dumps');

        // Create dumps directory
        mkdir($this->remoteDir . '/dumps', 0755, true);
        mkdir($this->localDir . '/dumps', 0755, true);
    }
}
