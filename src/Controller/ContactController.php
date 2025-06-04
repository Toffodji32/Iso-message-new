<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactGroup;
use App\Form\ContactForm; // Ajouté : Assurez-vous que ce Form Type existe bien
use App\Repository\ContactRepository; // Ajouté
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

#[Route('/contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contact_index', methods: ['GET'])]
    public function index(Request $request, ContactRepository $contactRepository): Response
    {
        // Récupérer le terme de recherche et l'ID du groupe depuis la requête
        $searchQuery = $request->query->get('q');
        $groupId = $request->query->get('group');

        // Récupérer les contacts en fonction des filtres
        // Nous allons créer une méthode dans le ContactRepository pour cela
        $contacts = $contactRepository->findByFilters($searchQuery, (int)$groupId); // Cast en int pour s'assurer du type

        // Créer un formulaire simple pour le filtre de groupe (sélecteur)
        // C'est un formulaire "non-mappé" (unmapped form) juste pour le champ de filtre
        $formBuilder = $this->createFormBuilder()
            ->add('group', EntityType::class, [
                'class' => ContactGroup::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un groupe', // Optionnel
                'required' => false,
                'mapped' => false, // Important : ce champ n'est pas mappé à l'entité Contact
                'data' => $groupId ? $contactRepository->getEntityManager()->getRepository(ContactGroup::class)->find($groupId) : null, // Pré-sélectionner le groupe si un filtre est appliqué
                'attr' => ['onchange' => 'this.form.submit()'], // Soumet le formulaire automatiquement
            ]);

        $filterForm = $formBuilder->getForm();

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
            'searchQuery' => $searchQuery, // Passer le terme de recherche pour le pré-remplir
            'filterForm' => $filterForm->createView(), // Passer la vue du formulaire de filtre
        ]);
    }

    #[Route('/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactForm::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Le contact a été créé avec succès.'); // Ajout d'un message flash

            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactForm::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le contact a été modifié avec succès.'); // Ajout d'un message flash

            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) { // Utilisez request->get('_token') pour les tokens de formulaire
            $entityManager->remove($contact);
            $entityManager->flush();
            $this->addFlash('success', 'Le contact a été supprimé avec succès.'); // Ajout d'un message flash
        } else {
            $this->addFlash('error', 'Token CSRF invalide.'); // Message d'erreur si le token est invalide
        }

        return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
    }
}
