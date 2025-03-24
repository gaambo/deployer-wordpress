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
        $this->assertTaskExists('files:sync');
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
        $this->assertTaskDependencies('files:sync', ['files:push', 'files:pull']);
    }
}
