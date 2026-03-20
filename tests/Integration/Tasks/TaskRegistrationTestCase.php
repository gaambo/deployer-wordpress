<?php

namespace Gaambo\DeployerWordpress\Tests\Integration\Tasks;

use Deployer\Task\GroupTask;
use Gaambo\DeployerWordpress\Tests\Integration\IntegrationTestCase;

abstract class TaskRegistrationTestCase extends IntegrationTestCase
{
    abstract protected static function loadTasks(): void;

    protected function setUp(): void
    {
        parent::setUp();
        // Require on each run, so they are registered again on the new Deployer instance.
        static::loadTasks();
    }

    protected function assertTaskExists(string $taskName): void
    {
        $this->assertTrue(
            $this->deployer->tasks->has($taskName),
            "Task '$taskName' should be registered"
        );
    }

    /**
     * @param string $taskName
     * @param string[] $expectedDependencies
     * @return void
     */
    protected function assertTaskDependencies(string $taskName, array $expectedDependencies): void
    {
        $task = $this->deployer->tasks->get($taskName);
        $this->assertInstanceOf(GroupTask::class, $task);
        $this->assertEquals($expectedDependencies, $task->getGroup());
    }

    abstract protected function testTasksAreRegistered(): void;
    abstract protected function testTaskDependencies(): void;
}
