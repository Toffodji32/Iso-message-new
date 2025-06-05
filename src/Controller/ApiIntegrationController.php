<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Form\ApiIntegrationSettingsType; // Vérifiez l'import
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\ByteString;

final class ApiIntegrationController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/settings/api', name: 'app_api_settings')]
    public function index(Request $request): Response
    {
        $apiKeySetting = $this->entityManager->getRepository(Setting::class)->findOneBy(['name' => 'sms_api_key']);

        if (!$apiKeySetting) {
            $apiKeySetting = new Setting();
            $apiKeySetting->setName('sms_api_key');
            $apiKeySetting->setValue(ByteString::fromRandom(32)->toString());
            $this->entityManager->persist($apiKeySetting);
            $this->entityManager->flush();
            $this->addFlash('success', 'Une nouvelle clé API a été générée.');
        }

        $currentApiKey = $apiKeySetting->getValue();

        // NOUVEAU : Créez le formulaire sans lui passer de données pour le bouton de soumission
        $form = $this->createForm(ApiIntegrationSettingsType::class); // PAS de $apiSettings ici

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Logique de régénération
            $apiKeySetting->setValue(ByteString::fromRandom(32)->toString());
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre clé API a été régénérée avec succès !');

            return $this->redirectToRoute('app_api_settings');
        }

        return $this->render('api_integration/index.html.twig', [
            'form' => $form->createView(),
            'current_api_key' => $currentApiKey,
        ]);
    }
}
