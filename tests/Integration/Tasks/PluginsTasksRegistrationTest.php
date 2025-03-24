<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class PluginsTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/plugins.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify plugins tasks are registered
        $this->assertTaskExists('plugins:push');
        $this->assertTaskExists('plugins:pull');
        $this->assertTaskExists('plugins:sync');
        $this->assertTaskExists('plugins:backup:remote');
        $this->assertTaskExists('plugins:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('plugins:sync', ['plugins:push', 'plugins:pull']);
    }
}
