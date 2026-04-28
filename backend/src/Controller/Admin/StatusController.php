<?php

namespace App\Controller\Admin;

use App\Entity\Status;
use App\Form\StatusType;
use App\Repository\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/status')]
#[IsGranted('ROLE_ADMIN')]
final class StatusController extends AbstractController
{
    #[Route('', name: 'admin_status_index', methods: ['GET'])]
    public function index(StatusRepository $repository): Response
    {
        return $this->render('admin/status/index.html.twig', [
            'statuss' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_status_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $entity = new Status();
        $form = $this->createForm(StatusType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirectToRoute('admin_status_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/status/new.html.twig', [
            'status' => $entity,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_status_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Status $entity, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(StatusType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_status_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/status/edit.html.twig', [
            'status' => $entity,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_status_delete', methods: ['POST'])]
    public function delete(Request $request, Status $entity, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirectToRoute('admin_status_index', [], Response::HTTP_SEE_OTHER);
    }
}
