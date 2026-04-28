<?php

namespace App\Controller\Front;

use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/front/search')]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    #[Route('', name: 'front_search', methods: ['GET'])]
    public function search(
        Request $request,
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository
    ): Response {
        $query    = $request->query->get('q', '');
        $statusFilter   = $request->query->get('status', '');
        $projectFilter  = $request->query->get('project', '');
        $userFilter     = $request->query->get('user', '');

        $currentUser = $this->getUser();

        $tasks    = [];
        $projects = $projectRepository->findForUser($currentUser);

        if ($query !== '' || $statusFilter !== '' || $projectFilter !== '' || $userFilter !== '') {
            $tasks = $taskRepository->search(
                $currentUser,
                $query,
                $statusFilter ?: null,
                $projectFilter ? (int) $projectFilter : null,
                $userFilter    ? (int) $userFilter    : null
            );
        }

        return $this->render('front/search/index.html.twig', [
            'tasks'         => $tasks,
            'projects'      => $projects,
            'query'         => $query,
            'statusFilter'  => $statusFilter,
            'projectFilter' => $projectFilter,
            'userFilter'    => $userFilter,
        ]);
    }
}
