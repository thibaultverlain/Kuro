<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notification')]
#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    #[Route('', name: 'notifications_index', methods: ['GET'])]
    public function index(NotificationRepository $repo): Response
    {
        $notifications = $repo->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('front/notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/ajax/unread', name: 'notifications_ajax_unread', methods: ['GET'])]
    public function unread(NotificationRepository $repo): Response
    {
        $notifications = $repo->findBy(
            ['user' => $this->getUser(), 'isRead' => false],
            ['createdAt' => 'DESC'],
            10 // max 10 dans le panel
        );

        return $this->render('front/notification/_ajax_list.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marque comme lu ET redirige vers le lien de la notification.
     * Gère proprement le cas où la ressource cible a été supprimée.
     */
    #[Route('/{id}/open', name: 'notifications_open', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function open(
        int $id,
        NotificationRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $notification = $repo->find($id);

        if (!$notification || $notification->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Notification introuvable.');
        }

        // Marquer comme lu
        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $em->flush();
        }

        // Rediriger vers le lien si valide, sinon vers la liste
        $link = $notification->getLink();
        if ($link) {
            return new RedirectResponse($link);
        }

        return $this->redirectToRoute('notifications_index');
    }

    #[Route('/{id}/read', name: 'notifications_mark_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markRead(
        int $id,
        NotificationRepository $repo,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $notification = $repo->find($id);

        if (!$notification || $notification->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Introuvable'], 404);
        }

        if (!$this->isCsrfTokenValid('notif_read', $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        $notification->setIsRead(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/read-all', name: 'notifications_read_all', methods: ['POST'])]
    public function markAllRead(
        NotificationRepository $repo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('notif_read_all', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $notifications = $repo->findBy(['user' => $this->getUser(), 'isRead' => false]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $em->flush();

        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');

        return $this->redirectToRoute('notifications_index');
    }
}
