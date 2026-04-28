<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Crée une notification pour un utilisateur.
     * Ne flush pas — délégué à l'appelant.
     */
    public function notify(
        User $user,
        string $type,
        string $message,
        ?string $link = null
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setMessage($message);
        $notification->setLink($link);
        $notification->setIsRead(false);

        $this->em->persist($notification);
    }
}
