<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

class ThemesTasksRegistrationTest extends TaskRegistrationTestCase
{
    protected static function loadTasks(): void
    {
        // Require on each run, so they are registered again on the new Deployer instance.
        require __DIR__ . '/../../../tasks/themes.php';
    }

    public function testTasksAreRegistered(): void
    {
        // Verify theme tasks are registered
        $this->assertTaskExists('theme:assets:vendors');
        $this->assertTaskExists('theme:assets:build');
        $this->assertTaskExists('theme:assets');
        $this->assertTaskExists('theme:vendors');
        $this->assertTaskExists('theme');
        $this->assertTaskExists('themes:push');
        $this->assertTaskExists('themes:pull');
        $this->assertTaskExists('themes:sync');
        $this->assertTaskExists('themes:backup:remote');
        $this->assertTaskExists('themes:backup:local');
    }

    public function testTaskDependencies(): void
    {
        // Verify task dependencies
        $this->assertTaskDependencies('theme:assets', ['theme:assets:vendors', 'theme:assets:build']);
        $this->assertTaskDependencies('theme', ['theme:assets', 'theme:vendors']);
        $this->assertTaskDependencies('themes:sync', ['themes:push', 'themes:pull']);
    }
}
