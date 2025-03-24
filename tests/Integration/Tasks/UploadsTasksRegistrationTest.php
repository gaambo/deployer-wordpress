<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class UploadsTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/uploads.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify uploads tasks are registered
        $this->assertTaskExists('uploads:push');
        $this->assertTaskExists('uploads:pull');
        $this->assertTaskExists('uploads:sync');
        $this->assertTaskExists('uploads:backup:remote');
        $this->assertTaskExists('uploads:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('uploads:sync', ['uploads:push', 'uploads:pull']);
    }
}
