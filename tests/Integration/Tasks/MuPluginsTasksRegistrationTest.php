<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class MuPluginsTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/mu-plugins.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify mu-plugin tasks are registered
        $this->assertTaskExists('mu-plugin:vendors');
        $this->assertTaskExists('mu-plugin');
        $this->assertTaskExists('mu-plugins:push');
        $this->assertTaskExists('mu-plugins:pull');
        $this->assertTaskExists('mu-plugins:sync');
        $this->assertTaskExists('mu-plugins:backup:remote');
        $this->assertTaskExists('mu-plugins:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('mu-plugin', ['mu-plugin:vendors']);
        $this->assertTaskDependencies('mu-plugins:sync', ['mu-plugins:push', 'mu-plugins:pull']);
    }
}
