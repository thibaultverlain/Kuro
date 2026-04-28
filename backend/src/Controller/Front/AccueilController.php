<?php

namespace App\Controller\Front;

use App\Repository\ProjectRepository;
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
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->getUser();

        // On charge uniquement les projets de l'utilisateur connecté
        $projects = $projectRepository->findForUser($currentUser);

        // On charge uniquement les tâches des projets de cet utilisateur
        $tasks = $taskRepository->findForUserProjects($currentUser);

        $users = $userRepository->findAll();

        $projectsData = [];

        foreach ($tasks as $task) {
            $projectName = $task->getProject()?->getName() ?? 'Sans projet';
            $statusName  = $task->getStatus()?->getName() ?? 'Inconnu';

            if (!isset($projectsData[$projectName])) {
                $projectsData[$projectName] = [
                    'En cours'  => 0,
                    'Terminée'  => 0,
                    'En retard' => 0,
                    'À faire'   => 0,
                ];
            }

            if (array_key_exists($statusName, $projectsData[$projectName])) {
                $projectsData[$projectName][$statusName]++;
            }
        }

        return $this->render('front/accueil/index.html.twig', [
            'projects'     => $projects,
            'tasks'        => $tasks,
            'users'        => $users,
            'projectsData' => $projectsData,
        ]);
    }
}
