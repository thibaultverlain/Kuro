<?php

namespace App\Controller\Front;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Security\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/front/project')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'front_project_index')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('front/project/index.html.twig', [
            'projects' => $projectRepository->findForUser($this->getUser()),
        ]);
    }

    #[Route('/{id}', name: 'front_project_show', requirements: ['id' => '\d+'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        return $this->render('front/project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/new', name: 'front_project_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project, [
            'available_users' => $userRepository->findByRole('ROLE_USER'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->addUser($this->getUser());
            $em->persist($project);
            $em->flush();

            return $this->redirectToRoute('front_project_index');
        }

        return $this->render('front/project/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'front_project_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Project $project,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);

        $form = $this->createForm(ProjectType::class, $project, [
            'available_users' => $userRepository->findAll(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('front_project_index');
        }

        return $this->render('front/project/edit.html.twig', [
            'form'    => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/{id}/delete', name: 'front_project_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Project $project,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::DELETE, $project);

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $em->remove($project);
            $em->flush();
        }

        return $this->redirectToRoute('front_project_index');
    }
}
