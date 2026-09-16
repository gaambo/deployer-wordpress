<?php

namespace Gaambo\DeployerWordpress\Tests\Functional\Tasks;

use Gaambo\DeployerWordpress\Tests\Functional\FunctionalTestCase;
use Gaambo\DeployerUtils\Runtime\DdevRuntime;
use RuntimeException;

use function Deployer\set;
use function Gaambo\DeployerUtils\runtime;

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
            "$wpBinary db export" => function ($host, $command, $options) use ($wpBinary) {
                $wpWorkingPath = $host->getAlias() === 'localhost'
                    ? $this->localHost->get('current_path')
                    : $this->remoteHost->get('release_or_current_path');
                $this->assertSame($wpWorkingPath, $this->runCwd($options));

                if (preg_match('/db export (.*?db_backup-\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.sql)(?:\s|$)/', $command, $matches)) {
                    $dumpFile = $matches[1];
                } else {
                    // Determine if this is a local or remote command based on the host
                    $dumpFile = $host->getAlias() === 'localhost'
                        ? $this->localDir . '/dumps/db_backup_' . date('Y-m-d_H-i') . '.sql'
                        : $this->remoteDir . '/dumps/db_backup_' . date('Y-m-d_H-i') . '.sql';
                }

                // Resolve relative dump paths against the WP-CLI working directory.
                if (!str_starts_with($dumpFile, '/') && !str_starts_with($dumpFile, '~')) {
                    $dumpFile = rtrim($wpWorkingPath, '/') . '/' . $dumpFile;
                }

                $dumpDir = dirname($dumpFile);
                if (!is_dir($dumpDir)) {
                    mkdir($dumpDir, 0755, true);
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

        $this->remoteHost->set('dbdump_path', $customRemotePath);
        $this->localHost->set('dbdump_path', $customLocalPath);

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
        $this->remoteHost->set('dbdump_path', $invalidPath);

        // Run the backup task and expect it to fail
        $this->mockSuccessfulDbExport();
        $result = $this->dep('db:remote:backup');
        $this->assertNotEquals(0, $result, 'Task should fail with invalid dump path');
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('db:remote:backup', $output);
        $this->assertStringContainsString('error', strtolower($output));

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

        $this->remoteHost->set('dbdump_path', $customRemotePath);
        $this->localHost->set('dbdump_path', $customLocalPath);

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
        $this->localHost->set('dbdump_path', $invalidPath);

        // Run the backup task and expect it to fail
        $this->mockSuccessfulDbExport();
        $result = $this->dep('db:local:backup');
        $this->assertNotEquals(0, $result, 'Task should fail with invalid dump path');
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('db:local:backup', $output);
        $this->assertStringContainsString('error', strtolower($output));

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
            'wp db import' => function ($host, $command, $options) {
                $wpWorkingPath = $host->getAlias() === 'localhost'
                    ? $this->localHost->get('current_path')
                    : $this->remoteHost->get('release_or_current_path');
                $this->assertSame($wpWorkingPath, $this->runCwd($options));

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

    public function testDbRemoteBackupWithRelativeDumpPath(): void
    {
        // Set relative dump paths
        $this->localHost->set('dbdump_path', 'data/db_dumps');
        $this->remoteHost->set('dbdump_path', 'data/db_dumps');

        // Create resolved dump directories
        $resolvedLocalPath = $this->localDir . '/data/db_dumps';
        $resolvedRemotePath = $this->remoteDir . '/data/db_dumps';
        mkdir($resolvedLocalPath, 0755, true);
        mkdir($resolvedRemotePath, 0755, true);

        // Mock successful WP-CLI export
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:remote:backup');
        $this->assertEquals(0, $result);

        // Verify dump file was created in resolved remote directory
        $remoteDumpFiles = glob($resolvedRemotePath . '/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be created in resolved relative path');

        // Verify dump file was downloaded to resolved local directory
        $localDumpFiles = glob($resolvedLocalPath . '/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be downloaded to resolved relative path');

        // Verify file contents match fixture
        $fixtureContent = file_get_contents($this->getFixturePath('database/dump.sql'));
        $remoteContent = file_get_contents($remoteDumpFiles[0]);
        $localContent = file_get_contents($localDumpFiles[0]);

        $this->assertEquals($fixtureContent, $remoteContent, 'Remote dump file should match fixture');
        $this->assertEquals($fixtureContent, $localContent, 'Local dump file should match fixture');
    }

    public function testDbLocalBackupWithRelativeDumpPath(): void
    {
        // Set relative dump paths
        $this->localHost->set('dbdump_path', 'data/db_dumps');
        $this->remoteHost->set('dbdump_path', 'data/db_dumps');

        // Create resolved dump directories
        $resolvedLocalPath = $this->localDir . '/data/db_dumps';
        $resolvedRemotePath = $this->remoteDir . '/data/db_dumps';
        mkdir($resolvedLocalPath, 0755, true);
        mkdir($resolvedRemotePath, 0755, true);

        // Mock successful WP-CLI export
        $this->mockSuccessfulDbExport();

        // Run the backup task
        $result = $this->dep('db:local:backup');
        $this->assertEquals(0, $result);

        // Verify dump file was created in resolved local directory
        $localDumpFiles = glob($resolvedLocalPath . '/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should be created in resolved relative path');

        // Verify dump file was uploaded to resolved remote directory
        $remoteDumpFiles = glob($resolvedRemotePath . '/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should be uploaded to resolved relative path');

        // Verify file contents match fixture
        $fixtureContent = file_get_contents($this->getFixturePath('database/dump.sql'));
        $localContent = file_get_contents($localDumpFiles[0]);
        $remoteContent = file_get_contents($remoteDumpFiles[0]);

        $this->assertEquals($fixtureContent, $localContent, 'Local dump file should match fixture');
        $this->assertEquals($fixtureContent, $remoteContent, 'Remote dump file should match fixture');
    }

    public function testDbPushWithRelativeDumpPath(): void
    {
        // Set relative dump paths
        $this->localHost->set('dbdump_path', 'data/db_dumps');
        $this->remoteHost->set('dbdump_path', 'data/db_dumps');
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create resolved dump directories
        $resolvedLocalPath = $this->localDir . '/data/db_dumps';
        $resolvedRemotePath = $this->remoteDir . '/data/db_dumps';
        mkdir($resolvedLocalPath, 0755, true);
        mkdir($resolvedRemotePath, 0755, true);

        // Mock successful WP-CLI export, import and URL replacement
        $this->mockSuccessfulDbExport();
        $this->mockSuccessfulDbImport();

        // Run the push task
        $result = $this->dep('db:push');
        $this->assertEquals(0, $result);

        // Verify remote dump file was imported and cleaned up
        $remoteDumpFiles = glob($resolvedRemotePath . '/db_backup-*.sql');
        $this->assertCount(0, $remoteDumpFiles, 'Remote dump file should be removed after import');

        // Verify local dump file was created
        $localDumpFiles = glob($resolvedLocalPath . '/db_backup-*.sql');
        $this->assertCount(1, $localDumpFiles, 'Local dump file should remain after push');
    }

    public function testDbPullWithRelativeDumpPath(): void
    {
        // Set relative dump paths
        $this->localHost->set('dbdump_path', 'data/db_dumps');
        $this->remoteHost->set('dbdump_path', 'data/db_dumps');
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create resolved dump directories
        $resolvedLocalPath = $this->localDir . '/data/db_dumps';
        $resolvedRemotePath = $this->remoteDir . '/data/db_dumps';
        mkdir($resolvedLocalPath, 0755, true);
        mkdir($resolvedRemotePath, 0755, true);

        // Mock successful WP-CLI export, import and URL replacement
        $this->mockSuccessfulDbExport();
        $this->mockSuccessfulDbImport();

        // Run the pull task
        $result = $this->dep('db:pull');
        $this->assertEquals(0, $result);

        // Verify local dump file was imported and cleaned up
        $localDumpFiles = glob($resolvedLocalPath . '/db_backup-*.sql');
        $this->assertCount(0, $localDumpFiles, 'Local dump file should be removed after import');

        // Verify remote dump file remains
        $remoteDumpFiles = glob($resolvedRemotePath . '/db_backup-*.sql');
        $this->assertCount(1, $remoteDumpFiles, 'Remote dump file should remain after pull');
    }

    /**
     * Helper method to mock successful multisite URL replacements
     */
    protected function mockSuccessfulMultisiteDbImport(string $hostName): void
    {
        $this->mockCommands([
            'wp db import' => function () {
                return 'Database imported successfully';
            },
            'wp search-replace' => function ($host, $command) {
                if (strpos($command, '--network --all-tables') !== false) {
                    return 'Made some replacements across all network tables';
                }
                return 'Made some replacements';
            }
        ], $hostName);
    }

    public function testDbLocalImportHandleMultisite(): void
    {
        // Enable multisite
        $this->remoteHost->set('wp/multisite', true);
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful multisite URL replacements
        $this->mockSuccessfulMultisiteDbImport('localhost');

        // Run the multisite import task
        $result = $this->dep('db:local:import');
        $output = $this->tester->getDisplay();
        $this->assertEquals(0, $result, 'Task should succeed with valid configuration');
    }

    public function testDbLocalImportHandleMultisiteWithUrlReplaceError(): void
    {
        // Enable multisite
        $this->remoteHost->set('wp/multisite', true);
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock failed URL replacement
        $this->mockCommands([
            'wp db import' => function () {
                return 'Database imported successfully';
            },
            'wp search-replace' => function ($host, $command) {
                if (strpos($command, '--network --all-tables') !== false) {
                    throw new RuntimeException('Multisite URL replacement failed');
                }
                return 'Made some replacements';
            }
        ], 'localhost');

        // Run the multisite import task and expect failure
        $result = $this->dep('db:local:import');
        $this->assertNotEquals(0, $result, 'Task should fail when URL replacement fails');
    }

    public function testDbRemoteImportHandleMultisite(): void
    {
        // Enable multisite
        $this->remoteHost->set('wp/multisite', true);
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock successful multisite URL replacements
        $this->mockSuccessfulMultisiteDbImport('testremote');

        // Run the multisite import task
        $result = $this->dep('db:remote:import');
        $this->assertEquals(0, $result, 'Task should succeed with valid configuration');
    }

    public function testDbRemoteImportHandleMultisiteWithUrlReplaceError(): void
    {
        // Enable multisite
        $this->remoteHost->set('wp/multisite', true);
        // Set up URLs for replacement
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');

        // Create a dump file to import
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        // Mock failed URL replacement
        $this->mockCommands([
            'wp db import' => function () {
                return 'Database imported successfully';
            },
            'wp search-replace' => function ($host, $command) {
                if (strpos($command, '--network --all-tables') !== false) {
                    throw new RuntimeException('Multisite URL replacement failed');
                }
                return 'Made some replacements';
            }
        ], 'testremote');

        // Run the multisite import task and expect failure
        $result = $this->dep('db:remote:import');
        $this->assertNotEquals(0, $result, 'Task should fail when URL replacement fails');
    }

    public function testDbLocalBackupWithDdevRuntime(): void
    {
        $this->localHost->set('runtime', runtime(DdevRuntime::class));
        $this->mockCommands([
            'wp db export' => function ($host, $command, $options) {
                $this->assertStringStartsWith('wp db export /var/www/html/dumps/', $command);
                $this->assertSame($this->localDir, $this->runCwd($options));
                $this->assertSame($this->ddevShell('/var/www/html/current'), $this->runShell($options));
                preg_match('/db export \/var\/www\/html\/dumps\/(db_backup-[^ ]+\.sql)/', $command, $matches);
                copy($this->getFixturePath('database/dump.sql'), $this->localDir . '/dumps/' . $matches[1]);
                return 'Database exported successfully';
            },
        ]);

        $result = $this->dep('db:local:backup');

        $this->assertSame(0, $result);
        $this->assertCount(1, glob($this->remoteDir . '/dumps/db_backup-*.sql'));
    }

    public function testDbRemoteBackupWithDdevRuntimeMapsDumpPath(): void
    {
        $this->remoteHost->set('runtime', runtime(DdevRuntime::class));
        $this->mockCommands([
            'wp db export' => function ($executionHost, $command, $options) {
                $this->assertStringStartsWith('wp db export /var/www/html/dumps/', $command);
                $this->assertSame($this->remoteDir, $this->runCwd($options));
                $this->assertSame($this->ddevShell('/var/www/html/current'), $this->runShell($options));
                preg_match('/db export \/var\/www\/html\/dumps\/(db_backup-[^ ]+\.sql)/', $command, $matches);
                copy($this->getFixturePath('database/dump.sql'), $this->remoteDir . '/dumps/' . $matches[1]);
                return 'Database exported successfully';
            },
        ], 'testremote');

        $result = $this->dep('db:remote:backup');

        $this->assertSame(0, $result);
        $this->assertCount(1, glob($this->localDir . '/dumps/db_backup-*.sql'));
    }

    public function testDbLocalImportWithDdevRuntimeMapsOnlyDumpPath(): void
    {
        $this->localHost->set('runtime', runtime(DdevRuntime::class));
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');
        $this->localHost->set('uploads/dir', '/local/uploads');
        $this->remoteHost->set('uploads/dir', '/remote/uploads');
        $dumpFile = $this->localDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');

        $commands = [];
        $this->mockCommands([
            'wp ' => function ($host, $command, $options) use (&$commands) {
                $commands[] = $command;
                $this->assertSame($this->localDir, $this->runCwd($options));
                $this->assertSame($this->ddevShell('/var/www/html/current'), $this->runShell($options));
                return '';
            },
        ]);

        $result = $this->dep('db:local:import');

        $this->assertSame(0, $result);
        $this->assertFileDoesNotExist($dumpFile);
        $this->assertStringContainsString('wp db import /var/www/html/dumps/db_backup.sql', $commands[0]);
        $this->assertContains('wp search-replace /remote/uploads /local/uploads ', $commands);
    }

    public function testDbRemoteImportWithDdevRuntimeMapsOnlyDumpPath(): void
    {
        $this->remoteHost->set('runtime', runtime(DdevRuntime::class));
        $this->localHost->set('public_url', 'http://localhost');
        $this->remoteHost->set('public_url', 'https://example.com');
        $dumpFile = $this->remoteDir . '/dumps/db_backup.sql';
        copy($this->getFixturePath('database/dump.sql'), $dumpFile);
        set('dbdump/file', 'db_backup.sql');
        $commands = [];
        $this->mockCommands([
            'wp ' => function ($executionHost, $command, $options) use (&$commands) {
                $commands[] = $command;
                $this->assertSame($this->remoteDir, $this->runCwd($options));
                $this->assertSame($this->ddevShell('/var/www/html/current'), $this->runShell($options));
                return '';
            },
        ], 'testremote');

        $result = $this->dep('db:remote:import');

        $this->assertSame(0, $result);
        $this->assertFileDoesNotExist($dumpFile);
        $this->assertStringContainsString('wp db import /var/www/html/dumps/db_backup.sql', $commands[0]);
        $this->assertContains('wp search-replace http://localhost https://example.com ', $commands);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set up required configuration
        $this->localHost->set('dbdump_path', $this->localDir . '/dumps');
        $this->remoteHost->set('dbdump_path', $this->remoteDir . '/dumps');

        // Create dumps directory
        mkdir($this->remoteDir . '/dumps', 0755, true);
        mkdir($this->localDir . '/dumps', 0755, true);
    }
}
