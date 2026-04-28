<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    private function makeProject(): Project
    {
        $p = new Project();
        $p->setName('BManager v2');
        $p->setDescription('Refonte complète.');
        return $p;
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $project = new Project();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($project->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $project->getCreatedAt());
        $this->assertLessThanOrEqual($after, $project->getCreatedAt());
    }

    public function testAddUserSyncsRelation(): void
    {
        $project = $this->makeProject();
        $user = new User();

        $project->addUser($user);

        $this->assertTrue($project->getUsers()->contains($user));
        $this->assertTrue($user->getProjects()->contains($project));
    }

    public function testAddUserIsIdempotent(): void
    {
        $project = $this->makeProject();
        $user = new User();

        $project->addUser($user);
        $project->addUser($user);

        $this->assertCount(1, $project->getUsers());
    }

    public function testRemoveUserSyncsRelation(): void
    {
        $project = $this->makeProject();
        $user = new User();

        $project->addUser($user);
        $project->removeUser($user);

        $this->assertFalse($project->getUsers()->contains($user));
    }

    public function testAddTaskSyncsRelation(): void
    {
        $project = $this->makeProject();
        $task = new Task();

        $project->addTask($task);

        $this->assertTrue($project->getTasks()->contains($task));
        $this->assertSame($project, $task->getProject());
    }

    public function testRemoveTaskNullsProjectReference(): void
    {
        $project = $this->makeProject();
        $task = new Task();

        $project->addTask($task);
        $project->removeTask($task);

        $this->assertFalse($project->getTasks()->contains($task));
        $this->assertNull($task->getProject());
    }
}
