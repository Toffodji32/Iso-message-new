<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ContactRepository;      // Garder si utilisé pour total_contacts
use App\Repository\ContactGroupRepository; // Garder si utilisé pour total_groups
use App\Repository\SmsLogRepository;      // Garder si utilisé pour sms_sent_today (que vous vouliez retirer)

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        ContactRepository $contactRepository,        // Garder si vous comptez toujours les contacts
        ?ContactGroupRepository $contactGroupRepository = null, // Garder si vous comptez toujours les groupes

    ): Response {
        // --- Récupération des statistiques (gardées mais 'sms_sent_today' n'est plus affichée) ---
        $totalContacts = $contactRepository->count([]);
        $totalGroups = $contactGroupRepository ? $contactGroupRepository->count([]) : 0;

        // La logique pour sms_sent_today reste ici si vous la voulez pour d'autres raisons,
        // mais elle n'est plus affichée dans le Twig.

        $stats = [
            'total_contacts' => $totalContacts,
            'total_groups' => $totalGroups,
        ];
        // --- Fin de la récupération des statistiques ---


        // NOUVEAU : SUPPRIMONS LA RÉCUPÉRATION DES CONTACTS RÉCENTS POUR ÉVITER L'ERREUR
        // La ligne suivante a été supprimée ou commentée :
        // $recentContacts = $contactRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'sms_balance' => 1000, // Remplacez par la logique réelle de solde SMS
            'stats' => $stats,
            // NOUVEAU : 'recent_contacts' n'est plus passé ici.
            // La ligne suivante a été supprimée ou commentée :
            // 'recent_contacts' => $recentContacts,
        ]);
    }
}
