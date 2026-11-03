<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class FilesTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/files.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify files tasks are registered
        $this->assertTaskExists('files:push');
        $this->assertTaskExists('files:pull');
        $this->assertTaskExists('files:backup:remote');
        $this->assertTaskExists('files:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('files:push', [
            'wp:push',
            'uploads:push',
            'plugins:push',
            'mu-plugins:push',
            'themes:push',
            'packages:push'
        ]);
        $this->assertTaskDependencies('files:pull', [
            'wp:pull',
            'uploads:pull',
            'plugins:pull',
            'mu-plugins:pull',
            'themes:pull',
            'packages:pull'
        ]);
    }

    public function testSharedBackupTasksLoadFromRelocatedTaskDirectory(): void
    {
        $taskDirectory = sys_get_temp_dir() . '/deployer-wordpress-tasks-' . bin2hex(random_bytes(8));
        mkdir($taskDirectory);

        try {
            foreach (glob(__DIR__ . '/../../../tasks/*.php') as $taskFile) {
                copy($taskFile, $taskDirectory . '/' . basename($taskFile));
            }

            require $taskDirectory . '/files.php';

            $this->assertTaskExists('files:backup:remote');
            $this->assertTaskExists('files:backup:local');
            $this->assertTaskExists('files:push');
            $this->assertTaskExists('files:pull');
        } finally {
            foreach (glob($taskDirectory . '/*.php') as $taskFile) {
                unlink($taskFile);
            }
            rmdir($taskDirectory);
        }
    }
}
