<?php

namespace App\Controller\Front;

use App\Repository\ProjectRepository;
use App\Repository\StatusRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccueilController extends AbstractController
{
    #[Route('/front', name: 'front_accueil')]
    public function accueil(
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        UserRepository $userRepository,
        StatusRepository $statusRepository
    ): Response {
        $currentUser = $this->getUser();

        $projects    = $projectRepository->findForUser($currentUser);
        $tasks       = $taskRepository->findForUserProjects($currentUser);
        $users       = $userRepository->findAll();
        $statuses    = $statusRepository->findAll();
        $statusNames = array_map(fn($s) => $s->getName(), $statuses);

        // Données graphique par projet — clés dynamiques depuis la BDD
        $projectsData = [];
        foreach ($tasks as $task) {
            $projectName = $task->getProject()?->getName() ?? 'Sans projet';
            $statusName  = $task->getStatus()?->getName() ?? 'Inconnu';

            if (!isset($projectsData[$projectName])) {
                $projectsData[$projectName] = array_fill_keys($statusNames, 0);
            }

            if (isset($projectsData[$projectName][$statusName])) {
                $projectsData[$projectName][$statusName]++;
            }
        }

        $activityData = $taskRepository->getActivityLastDays($currentUser, 7);

        return $this->render('front/accueil/index.html.twig', [
            'projects'     => $projects,
            'tasks'        => $tasks,
            'users'        => $users,
            'statuses'     => $statuses,
            'statusNames'  => $statusNames,
            'projectsData' => $projectsData,
            'activityData' => $activityData,
        ]);
    }
}
