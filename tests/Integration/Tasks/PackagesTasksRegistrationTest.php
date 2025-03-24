<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class PackagesTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/packages.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify package tasks are registered
        $this->assertTaskExists('packages:assets:vendors');
        $this->assertTaskExists('packages:assets:build');
        $this->assertTaskExists('packages:assets');
        $this->assertTaskExists('packages:vendors');
        $this->assertTaskExists('packages');
        $this->assertTaskExists('packages:push');
        $this->assertTaskExists('packages:pull');
        $this->assertTaskExists('packages:sync');
        $this->assertTaskExists('packages:backup:remote');
        $this->assertTaskExists('packages:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('packages:assets', ['packages:assets:vendors', 'packages:assets:build']);
        $this->assertTaskDependencies('packages', ['packages:assets', 'packages:vendors']);
        $this->assertTaskDependencies('packages:sync', ['packages:push', 'packages:pull']);
    }
}
