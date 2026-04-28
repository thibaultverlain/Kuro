<?php

namespace App\Tests\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    public function testNotifyPersistsWithoutFlushing(): void
    {
        $user = new User();

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Notification::class));

        $em->expects($this->never())
            ->method('flush');

        $service = new NotificationService($em);
        $service->notify($user, 'task_assigned', 'Vous avez une nouvelle tâche.', '/front/tasks/1');
    }

    public function testNotifyWithNullLink(): void
    {
        $user = new User();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');

        $service = new NotificationService($em);
        $service->notify($user, 'task_created', 'Tâche créée.', null);
        $this->assertTrue(true);
    }
}
