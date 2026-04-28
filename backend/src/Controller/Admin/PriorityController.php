<?php

namespace App\Controller\Admin;

use App\Entity\Priority;
use App\Form\PriorityType;
use App\Repository\PriorityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/priority')]
#[IsGranted('ROLE_ADMIN')]
final class PriorityController extends AbstractController
{
    #[Route('', name: 'admin_priority_index', methods: ['GET'])]
    public function index(PriorityRepository $repository): Response
    {
        return $this->render('admin/priority/index.html.twig', [
            'prioritys' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_priority_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $entity = new Priority();
        $form = $this->createForm(PriorityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirectToRoute('admin_priority_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/priority/new.html.twig', [
            'priority' => $entity,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_priority_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Priority $entity, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PriorityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_priority_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/priority/edit.html.twig', [
            'priority' => $entity,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_priority_delete', methods: ['POST'])]
    public function delete(Request $request, Priority $entity, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirectToRoute('admin_priority_index', [], Response::HTTP_SEE_OTHER);
    }
}
