<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom,
        private string $mailerName,
    ) {}

    /**
     * Crée une notification interne ET envoie un email si l'utilisateur l'a activé.
     * Ne flush pas — délégué à l'appelant.
     */
    public function notify(
        User $user,
        string $type,
        string $message,
        ?string $link = null
    ): void {
        // 1. Notification interne (toujours)
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setMessage($message);
        $notification->setLink($link);
        $notification->setIsRead(false);

        $this->em->persist($notification);

        // 2. Email (si préférence activée et email valide)
        $prefs = $user->getNotificationPreferences();
        if (!empty($prefs[$type]) && $user->getEmail()) {
            $this->sendEmail($user, $type, $message, $link);
        }
    }

    private function sendEmail(User $user, string $type, string $message, ?string $link): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->mailerFrom, $this->mailerName))
                ->to(new Address($user->getEmail(), $user->getName() ?? $user->getEmail()))
                ->subject($this->getSubject($type))
                ->htmlTemplate('emails/notification.html.twig')
                ->context([
                    'user'    => $user,
                    'type'    => $type,
                    'message' => $message,
                    'link'    => $link,
                ]);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // On ne fait jamais planter l'appli pour un email raté
            $this->logger->warning('Email de notification non envoyé : ' . $e->getMessage(), [
                'user' => $user->getEmail(),
                'type' => $type,
            ]);
        }
    }

    private function getSubject(string $type): string
    {
        return match ($type) {
            'task_assigned' => '[Kuro] Une tâche vous a été assignée',
            'task_created'  => '[Kuro] Nouvelle tâche créée',
            'task_updated'  => '[Kuro] Une tâche a été modifiée',
            'task_deleted'  => '[Kuro] Une tâche a été supprimée',
            default         => '[Kuro] Nouvelle notification',
        };
    }
}
