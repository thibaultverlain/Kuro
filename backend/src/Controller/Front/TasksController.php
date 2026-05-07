<?php

namespace App\Controller\Front;

use App\Entity\Task;
use App\Entity\Status;
use App\Entity\Priority;
use App\Entity\Project;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Security\ProjectVoter;
use App\Service\TaskHistoryService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/front/tasks')]
#[IsGranted('ROLE_USER')]
final class TasksController extends AbstractController
{
    #[Route('/project/{id}', name: 'front_tasks_by_project', methods: ['GET'])]
    public function byProject(Project $project, TaskRepository $taskRepository): Response
    {
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        return $this->render('front/tasks/index.html.twig', [
            'project' => $project,
            'tasks'   => $taskRepository->findBy(['project' => $project]),
        ]);
    }

    #[Route('/new/{projectId}', name: 'front_tasks_new', methods: ['GET', 'POST'])]
    public function new(
        int $projectId,
        Request $request,
        EntityManagerInterface $em,
        TaskHistoryService $history,
        NotificationService $notificationService
    ): Response {
        $project = $em->getRepository(Project::class)->find($projectId);

        if (!$project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $task = new Task();
        $task->setProject($project);

        $assignableUsers = $project->getUsers()
            ->filter(fn($u) => in_array('ROLE_USER', $u->getRoles()))
            ->toArray();

        $form = $this->createForm(TaskType::class, $task, ['project_users' => $assignableUsers]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($task->getUsers() as $user) {
                if (!$project->getUsers()->contains($user)) {
                    throw $this->createAccessDeniedException('Utilisateur non autorisé.');
                }
            }

            if (!$task->getStatus()) {
                $defaultStatus = $em->getRepository(Status::class)->findOneBy(['name' => 'À faire']);
                if ($defaultStatus) {
                    $task->setStatus($defaultStatus);
                }
            }

            if (!$task->getPriority()) {
                $defaultPriority = $em->getRepository(Priority::class)->findOneBy(['name' => 'Moyenne']);
                if ($defaultPriority) {
                    $task->setPriority($defaultPriority);
                }
            }

            $em->persist($task);
            $em->flush();
            $history->log($task, 'Tâche créée', $this->getUser());

            foreach ($task->getUsers() as $user) {
                if ($user !== $this->getUser()) {
                    $notificationService->notify(
                        $user,
                        'task_assigned',
                        sprintf('%s vous a assigné la tâche "%s" dans le projet "%s".', $this->getUser()->getName(), $task->getTitle(), $project->getName()),
                        $this->generateUrl('front_tasks_show', ['id' => $task->getId()])
                    );
                }
            }

            $notificationService->notify(
                $this->getUser(),
                'task_created',
                sprintf('Vous avez créé la tâche "%s" dans le projet "%s".', $task->getTitle(), $project->getName()),
                $this->generateUrl('front_tasks_show', ['id' => $task->getId()])
            );

            

            return $this->redirectToRoute('front_tasks_by_project', ['id' => $project->getId()]);
        }

        return $this->render('front/tasks/new.html.twig', [
            'task'    => $task,
            'form'    => $form,
            'project' => $project,
        ]);
    }

    #[Route('/show/{id}', name: 'front_tasks_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $task->getProject());

        return $this->render('front/tasks/show.html.twig', ['task' => $task]);
    }

    #[Route('/{id}/edit', name: 'front_tasks_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Task $task,
        EntityManagerInterface $em,
        TaskHistoryService $history,
        NotificationService $notificationService
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $task->getProject());

        $oldUsers = clone $task->getUsers();
        $assignableUsers = $task->getProject()->getUsers()
            ->filter(fn($u) => in_array('ROLE_USER', $u->getRoles()))
            ->toArray();

        $form = $this->createForm(TaskType::class, $task, ['project_users' => $assignableUsers]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $history->log($task, 'Tâche modifiée', $this->getUser());

            foreach ($task->getUsers() as $user) {
                if (!$oldUsers->contains($user) && $user !== $this->getUser()) {
                    $notificationService->notify(
                        $user,
                        'task_assigned',
                        sprintf('%s vous a ajouté à la tâche "%s" dans le projet "%s".', $this->getUser()->getName(), $task->getTitle(), $task->getProject()->getName()),
                        $this->generateUrl('front_tasks_show', ['id' => $task->getId()])
                    );
                }
            }

            $notificationService->notify(
                $this->getUser(),
                'task_updated',
                sprintf('Vous avez modifié la tâche "%s" dans le projet "%s".', $task->getTitle(), $task->getProject()->getName()),
                $this->generateUrl('front_tasks_show', ['id' => $task->getId()])
            );

            $em->flush();

            return $this->redirectToRoute('front_tasks_show', ['id' => $task->getId()]);
        }

        return $this->render('front/tasks/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/status', name: 'front_tasks_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        Task $task,
        EntityManagerInterface $em,
        TaskHistoryService $history
    ): JsonResponse {
        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $task->getProject());

        $data = json_decode($request->getContent(), true);

        if (!$this->isCsrfTokenValid('task_status', $data['_token'] ?? '')) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 400);
        }

        $newStatusName = $data['status'] ?? null;
        if (!$newStatusName) {
            return new JsonResponse(['error' => 'Statut manquant'], 400);
        }

        $status = $em->getRepository(Status::class)->findOneBy(['name' => $newStatusName]);
        if (!$status) {
            return new JsonResponse(['error' => 'Statut inconnu'], 400);
        }

        $task->setStatus($status);
        $history->log($task, "Statut modifié en : $newStatusName", $this->getUser());
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/delete', name: 'front_tasks_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Task $task,
        EntityManagerInterface $em,
        TaskHistoryService $history
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::DELETE, $task->getProject());

        $project = $task->getProject();

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $history->log($task, 'Tâche supprimée', $this->getUser());
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('front_tasks_by_project', ['id' => $project->getId()]);
    }
}
