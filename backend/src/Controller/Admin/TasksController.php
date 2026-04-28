<?php

namespace App\Controller\Admin;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tasks')]
#[IsGranted('ROLE_ADMIN')]
final class TasksController extends AbstractController
{
    #[Route('', name: 'admin_tasks_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        return $this->render('admin/tasks/index.html.twig', [
            'tasks' => $taskRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'admin_tasks_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->render('admin/tasks/show.html.twig', ['task' => $task]);
    }

    #[Route('/{id}/edit', name: 'admin_tasks_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $task, [
            'project_users' => $task->getProject()->getUsers()->toArray(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_tasks_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tasks/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_tasks_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('admin_tasks_index', [], Response::HTTP_SEE_OTHER);
    }
}
