<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class WpTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/wp.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify wp tasks are registered
        $this->assertTaskExists('wp:download-core');
        $this->assertTaskExists('wp:push');
        $this->assertTaskExists('wp:pull');
        $this->assertTaskExists('wp:info');
        $this->assertTaskExists('wp:install-wpcli');
    }

    /**
     * @doesNotPerformAssertions
     * @return void
     */
    public function testTaskDependencies(): void
    {
        // No grouped tasks in wp.php
    }
}
