<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SmsHistoriqueController extends AbstractController
{
    #[Route('/sms/historique', name: 'app_sms_historique')]
    public function index(): Response
    {
        return $this->render('sms_historique/index.html.twig', [
            'controller_name' => 'SmsHistoriqueController',
        ]);
    }
}
