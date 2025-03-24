<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class DatabaseTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/database.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify database tasks are registered
        $this->assertTaskExists('db:remote:backup');
        $this->assertTaskExists('db:local:backup');
        $this->assertTaskExists('db:remote:import');
        $this->assertTaskExists('db:local:import');
        $this->assertTaskExists('db:push');
        $this->assertTaskExists('db:pull');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('db:push', ['db:local:backup', 'db:remote:import']);
        $this->assertTaskDependencies('db:pull', ['db:remote:backup', 'db:local:import']);
    }
}
