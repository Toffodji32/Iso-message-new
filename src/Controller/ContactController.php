<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactGroup;
use App\Form\ContactForm; 
use App\Repository\ContactRepository; 
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
    
        $searchQuery = $request->query->get('q');
        $groupId = $request->query->get('group');

        
        $contacts = $contactRepository->findByFilters($searchQuery, (int)$groupId); 

        
        $formBuilder = $this->createFormBuilder()
            ->add('group', EntityType::class, [
                'class' => ContactGroup::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un groupe',
                'placeholder' => 'Sélectionner un groupe',
                'required' => false,
                'mapped' => false, 
                'data' => $groupId ? $contactRepository->getEntityManager()->getRepository(ContactGroup::class)->find($groupId) : null, 
                'attr' => ['onchange' => 'this.form.submit()'], 
            ]);

        $filterForm = $formBuilder->getForm();

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
            'searchQuery' => $searchQuery,
            'filterForm' => $filterForm->createView(),
            'searchQuery' => $searchQuery,
            'filterForm' => $filterForm->createView(),
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
        
            if ($request->isXmlHttpRequest()) {
        
            return new Response('', Response::HTTP_NO_CONTENT);
        }

            $this->addFlash('success', 'Le contact a été créé avec succès.'); 

            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($request->isXmlHttpRequest()) {
            return $this->render('contact/_form.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
            'contact' => $contact,
            ]);
        }


        return $this->render('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form->createView(),
        ]);
    }



    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(ContactForm::class, $contact);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        // ✅ Si AJAX : 204 = No Content (modale se ferme et reload via JS)
        if ($request->isXmlHttpRequest()) {
            return new Response(null, 204);
        }

        $this->addFlash('success', 'Le contact a été modifié avec succès.');
        return $this->redirectToRoute('app_contact_index');
    }

    // ✅ Si AJAX : on retourne uniquement le formulaire (sans base)
    if ($request->isXmlHttpRequest()) {
        return $this->render('contact/_form.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Modifier',
            'contact' => $contact,
        ]);
    }

    // ✅ Sinon, affichage classique complet
    return $this->render('contact/edit.html.twig', [
        'contact' => $contact, 
        'form' => $form->createView(), // <-- CORRECTION ICI
    ]);
}


    #[Route('/contacts/export', name: 'app_contact_export')]
public function export(EntityManagerInterface $em): Response
{
    $contacts = $em->getRepository(Contact::class)->findAll();

    $filename = 'contacts_' . date('Ymd_His') . '.csv';


    ob_start();

    echo "\xEF\xBB\xBF";

    $handle = fopen('php://output', 'w+');

    fputcsv($handle, ['ID', 'Téléphone', 'Prénom', 'Nom', 'Email', 'Groupes'], ';');

    foreach ($contacts as $contact) {
        $groupNames = array_map(fn($g) => $g->getName(), $contact->getContactGroups()->toArray());
        fputcsv($handle, [
            $contact->getId(),
            $contact->getPhoneNumber(),
            $contact->getFirstName(),
            $contact->getLastName(),
            $contact->getEmail(),
            implode(', ', $groupNames)
        ], ';'); 
    }

    fclose($handle);

    $content = ob_get_clean();

    return new Response($content, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}



    #[Route('/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) { 
            $entityManager->remove($contact);
            $entityManager->flush();
            $this->addFlash('success', 'Le contact a été supprimé avec succès.'); 
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
    }
}
