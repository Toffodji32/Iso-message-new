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

    $actionUrl = $this->generateUrl('app_contact_group_new');


    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($contactGroup);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        return $this->redirectToRoute('app_contact_group_index');
    }


    if ($request->isXmlHttpRequest()) {
        $formHtml = $this->renderView('contact_group/_form.html.twig', [
            'form' => $form->createView(),
            'contact_group' => $contactGroup,
            'button_label' => 'Créer',
            'action_url' => $actionUrl,
        ]);

        return $form->isSubmitted()
            ? $this->json(['success' => false, 'form' => $formHtml])
            : new Response($formHtml);
    }

    
    return $this->render('contact_group/new.html.twig', [
        'form' => $form->createView(),
        'contact_group' => $contactGroup,
        'action_url' => $actionUrl,
    ]);
}

    #[Route('/{id}/edit', name: 'app_contact_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContactGroup $contactGroup, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactGroupForm::class, $contactGroup);
        $form->handleRequest($request);

        $actionUrl = $this->generateUrl('app_contact_group_edit', ['id' => $contactGroup->getId()]);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true]);
                }

                return $this->redirectToRoute('app_contact_group_index');
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('contact_group/_form.html.twig', [
                        'form' => $form->createView(),
                        'contact_group' => $contactGroup,
                        'button_label' => 'Modifier',
                        'action_url' => $actionUrl,
                    ])
                ]);
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('contact_group/_form.html.twig', [
                'form' => $form->createView(),
                'contact_group' => $contactGroup,
                'button_label' => 'Modifier',
                'action_url' => $actionUrl,
            ]);
        }

        return $this->render('contact_group/edit.html.twig', [
            'form' => $form->createView(),
            'contact_group' => $contactGroup,
            'action_url' => $actionUrl,
        ]);
    }
    

    #[Route('/{id}', name: 'app_contact_group_delete', methods: ['POST'])]
    public function delete(Request $request, ContactGroup $contactGroup, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $contactGroup->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contactGroup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contact_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
