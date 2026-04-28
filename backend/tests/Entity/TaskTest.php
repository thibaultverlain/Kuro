<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\Priority;
use App\Entity\Status;
use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    private function makeTask(): Task
    {
        $task = new Task();
        $task->setTitle('Corriger le bug de login');
        $task->setDescription('Le formulaire renvoie une 500 en prod.');
        return $task;
    }

    public function testConstructorSetsTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        $task = new Task();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($task->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $task->getCreatedAt());
        $this->assertLessThanOrEqual($after, $task->getCreatedAt());
    }

    public function testSetTitleUpdatesLastChangedAt(): void
    {
        $task = $this->makeTask();
        $before = $task->getLastChangedAt();

        usleep(1000); // 1ms
        $task->setTitle('Nouveau titre');

        $this->assertGreaterThan($before, $task->getLastChangedAt());
    }

    public function testIsLateReturnsFalseWithoutDueDate(): void
    {
        $task = $this->makeTask();
        $this->assertFalse($task->isLate());
    }

    public function testIsLateReturnsTrueWhenOverdue(): void
    {
        $task = $this->makeTask();
        $task->setDueDate(new \DateTimeImmutable('yesterday'));
        $this->assertTrue($task->isLate());
    }

    public function testIsLateReturnsFalseWhenDone(): void
    {
        $status = $this->createMock(Status::class);
        $status->method('getName')->willReturn('terminée');

        $task = $this->makeTask();
        $task->setDueDate(new \DateTimeImmutable('yesterday'));
        $task->setStatus($status);

        $this->assertFalse($task->isLate());
    }

    public function testIsLateReturnsFalseForFutureDate(): void
    {
        $task = $this->makeTask();
        $task->setDueDate(new \DateTimeImmutable('+1 month'));
        $this->assertFalse($task->isLate());
    }

    public function testAddUserSyncsRelation(): void
    {
        $task = $this->makeTask();
        $user = new User();

        $task->addUser($user);

        $this->assertTrue($task->getUsers()->contains($user));
        $this->assertTrue($user->getTasks()->contains($task));
    }

    public function testAddUserIsIdempotent(): void
    {
        $task = $this->makeTask();
        $user = new User();

        $task->addUser($user);
        $task->addUser($user);

        $this->assertCount(1, $task->getUsers());
    }

    public function testRemoveUserSyncsRelation(): void
    {
        $task = $this->makeTask();
        $user = new User();

        $task->addUser($user);
        $task->removeUser($user);

        $this->assertFalse($task->getUsers()->contains($user));
        $this->assertFalse($user->getTasks()->contains($task));
    }

    public function testDefaultPositionIsZero(): void
    {
        $task = new Task();
        $this->assertSame(0, $task->getPosition());
    }
}
