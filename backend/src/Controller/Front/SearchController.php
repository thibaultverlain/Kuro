<?php

namespace App\Controller\Front;

use App\Repository\ProjectRepository;
use App\Repository\StatusRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
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
        ProjectRepository $projectRepository,
        StatusRepository $statusRepository,
        UserRepository $userRepository
    ): Response {
        $query         = trim($request->query->get('q', ''));
        $statusFilter  = $request->query->get('status', '');
        $projectFilter = $request->query->get('project', '');
        $userFilter    = $request->query->get('user', '');

        $currentUser = $this->getUser();
        $projects    = $projectRepository->findForUser($currentUser);
        $statuses    = $statusRepository->findAll();
        $tasks       = [];
        $hasFilter   = $query !== '' || $statusFilter !== '' || $projectFilter !== '' || $userFilter !== '';

        // Charger uniquement les membres des projets accessibles à l'utilisateur
        $projectIds = array_map(fn($p) => $p->getId(), $projects);
        $members    = $userRepository->findMembersOfProjects($projectIds);

        if ($hasFilter) {
            $tasks = $taskRepository->search(
                $currentUser,
                $query,
                $statusFilter  ?: null,
                $projectFilter ? (int) $projectFilter : null,
                $userFilter    ? (int) $userFilter    : null
            );
        }

        return $this->render('front/search/index.html.twig', [
            'tasks'         => $tasks,
            'projects'      => $projects,
            'statuses'      => $statuses,
            'members'       => $members,
            'query'         => $query,
            'statusFilter'  => $statusFilter,
            'projectFilter' => $projectFilter,
            'userFilter'    => $userFilter,
            'hasFilter'     => $hasFilter,
        ]);
    }
}
