<?php

namespace App\Controller;

use App\Entity\ContactGroup;
use App\Form\ContactGroupForm;
use App\Repository\ContactGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact_group')]
final class ContactGroupController extends AbstractController
{
    #[Route(name: 'app_contact_group_index', methods: ['GET'])]
    public function index(ContactGroupRepository $contactGroupRepository): Response
    {
        return $this->render('contact_group/index.html.twig', [
            'contact_groups' => $contactGroupRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_contact_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contactGroup = new ContactGroup();
        $form = $this->createForm(ContactGroupForm::class, $contactGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contactGroup);
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact_group/new.html.twig', [
            'contact_group' => $contactGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_group_show', methods: ['GET'])]
    public function show(ContactGroup $contactGroup): Response
    {
        return $this->render('contact_group/show.html.twig', [
            'contact_group' => $contactGroup,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContactGroup $contactGroup, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactGroupForm::class, $contactGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact_group/edit.html.twig', [
            'contact_group' => $contactGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_group_delete', methods: ['POST'])]
    public function delete(Request $request, ContactGroup $contactGroup, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contactGroup->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contactGroup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contact_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
