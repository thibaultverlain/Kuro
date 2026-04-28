<?php

namespace App\Tests\Service;

use App\Entity\Task;
use App\Entity\TaskHistory;
use App\Entity\User;
use App\Service\TaskHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TaskHistoryServiceTest extends TestCase
{
    public function testLogPersistsEntryWithoutFlushing(): void
    {
        $task   = new Task();
        $author = new User();

        $em = $this->createMock(EntityManagerInterface::class);

        // persist() doit être appelé exactement une fois
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(TaskHistory::class));

        // flush() ne doit PAS être appelé par le service
        $em->expects($this->never())
            ->method('flush');

        $service = new TaskHistoryService($em);
        $service->log($task, 'Tâche créée', $author);
    }

    public function testLogWorksWithoutAuthor(): void
    {
        $task = new Task();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');

        $service = new TaskHistoryService($em);
        $service->log($task, 'Action système');
        // Pas d'exception = succès
        $this->assertTrue(true);
    }
}
