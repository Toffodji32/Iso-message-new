<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        
        $form = $this->createFormBuilder($user)
            ->add('fullName', TextType::class, ['label' => 'Nom complet'])
            ->add('phoneNumber', TextType::class, ['label' => 'Numéro de téléphone', 'required' => false])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer les modifications'])
            ->getForm();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(), // ⬅️ Tu dois ajouter cette ligne
        ]);
    }
}
