<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class LanguagesTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/languages.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify languages tasks are registered
        $this->assertTaskExists('languages:push');
        $this->assertTaskExists('languages:pull');
        $this->assertTaskExists('languages:sync');
        $this->assertTaskExists('languages:backup:remote');
        $this->assertTaskExists('languages:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('languages:sync', ['languages:push', 'languages:pull']);
    }
}
