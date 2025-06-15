<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ContactRepository;
use App\Repository\ContactGroupRepository;
use App\Repository\SmsLogRepository;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ContactRepository $contactRepository,
        ?ContactGroupRepository $contactGroupRepository = null,

    ): Response {

        $totalContacts = $contactRepository->count([]);
        $totalGroups = $contactGroupRepository ? $contactGroupRepository->count([]) : 0;



        $stats = [
            'total_contacts' => $totalContacts,
            'total_groups' => $totalGroups,
        ];



        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'sms_balance' => 1000,
            'stats' => $stats,
        ]);
    }
}
