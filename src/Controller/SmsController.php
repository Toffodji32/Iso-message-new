<?php

namespace App\Controller;

use App\Form\SmsMessageTypeForm;
use App\Entity\Contact;
use App\Entity\ContactGroup;
use App\Service\SmsSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class SmsController extends AbstractController
{
    private $entityManager;

    private $smsSender;
    private $security;


    public function __construct(EntityManagerInterface $entityManager, SmsSender $smsSender)
    {
        $this->entityManager = $entityManager;

        $this->smsSender = $smsSender;
        
    }

    #[Route('/sms/send', name: 'app_send_sms')]
    public function send(Request $request): Response
    {
        $form = $this->createForm(SmsMessageTypeForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $messageContent = $data['messageContent'];
            $scheduleAt = $data['scheduleAt'];

            $recipients = [];

            // 1. Gérer les numéros saisis directement
            if (!empty($data['directNumbers'])) {
                $rawNumbers = preg_split('/[\r\n]+/', $data['directNumbers'], -1, PREG_SPLIT_NO_EMPTY);
                foreach ($rawNumbers as $number) {
                    $recipients[] = trim($number);
                }
            }

            // 2. Gérer les contacts sélectionnés (si les champs sont activés et utilisés)
            if ($data['contacts']) {
                foreach ($data['contacts'] as $contact) {
                    if ($contact instanceof Contact) {
                        $recipients[] = $contact->getPhoneNumber();
                    }
                }
            }

            // 3. Gérer les groupes de contacts sélectionnés (si les champs sont activés et utilisés)
            if ($data['contactGroups']) {
                foreach ($data['contactGroups'] as $group) {
                    if ($group instanceof ContactGroup) {
                        foreach ($group->getContacts() as $contact) {
                            $recipients[] = $contact->getPhoneNumber();
                        }
                    }
                }
            }

            // Supprimer les doublons et nettoyer les numéros
            $recipients = array_unique(array_filter($recipients));

            if (empty($recipients)) {
                $this->addFlash('error', 'Veuillez spécifier au moins un destinataire (numéro direct, contact ou groupe).');
                return $this->redirectToRoute('app_send_sms');
            }

            $numberOfRecipients = count($recipients);
            $costPerSms = 1; // Coût par SMS, à adapter selon votre logique (gardé pour information, mais non débité)
            $totalCost = $numberOfRecipients * $costPerSms; // Gardé pour information

            $user = $this->security->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour envoyer des SMS.');
                return $this->redirectToRoute('app_login'); // Rediriger vers la page de connexion si non connecté
            }

            // --- Suppression de la vérification du solde ---
            // Le solde ne sera PAS vérifié ici.
            // Le solde de l'utilisateur ne sera PAS débité.
            $this->addFlash('info', 'La vérification du solde et le débit des crédits sont actuellement désactivés.');


            $sentCount = 0;
            $failedCount = 0;
            $scheduledCount = 0;

            if ($scheduleAt === null) { // Envoi immédiat
                foreach ($recipients as $recipient) {
                    // Logique d'envoi de SMS via le service SmsSender
                    if ($this->smsSender->sendSms($recipient, $messageContent)) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                }
                $this->addFlash('success', sprintf('%d SMS transmis à l\'opérateur, %d échecs de transmission.', $sentCount, $failedCount));

            } else { // SMS planifié
                // Pour l'instant, sans SmsLog, nous ne pouvons pas réellement "planifier" l'envoi.
                // Nous allons juste notifier que l'action est "planifiée".
                $scheduledCount = $numberOfRecipients;
                $this->addFlash('success', sprintf('%d SMS planifiés (mais non stockés pour le moment) avec succès pour le %s à %s.', $scheduledCount, $scheduleAt->format('d/m/Y'), $scheduleAt->format('H:i')));
                $this->addFlash('info', 'Les SMS planifiés ne seront pas envoyés automatiquement tant que l\'entité SmsLog et un système de planification ne seront pas mis en place.');
            }

            // Rediriger vers une page de rapport ou l'index après l'envoi
            return $this->redirectToRoute('app_contact_index'); // Redirection temporaire
        }

        return $this->render('sms/send_sms.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
