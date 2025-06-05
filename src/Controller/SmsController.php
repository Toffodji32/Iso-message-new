<?php

namespace App\Controller;

use App\Form\SmsMessageTypeForm;
use App\Entity\Contact;
use App\Entity\ContactGroup;
use App\Entity\SmsMessage;
use App\Entity\SmsRecipient; 
use App\Service\SmsSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

final class SmsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SmsSender $smsSender;
    private Security $security;


    public function __construct(EntityManagerInterface $entityManager, SmsSender $smsSender, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->smsSender = $smsSender;
        $this->security = $security; // Initialisez la propriété security
    }

    #[Route('/sms/send', name: 'app_send_sms')]
    public function send(Request $request): Response
    {
        $form = $this->createForm(SmsMessageTypeForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $messageContent = $data['messageContent'];
            $scheduleAt = $data['scheduleAt']; // Sera null pour un envoi immédiat

            $user = $this->security->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour envoyer des SMS.');
                return $this->redirectToRoute('app_login');
            }

            $recipients = [];
            $smsType = $data['smsType']; // Récupérer le type de SMS (classic, flash)
            $sender = $data['sender'];   // Récupérer l'expéditeur

            // Logique de récupération des destinataires basée sur recipientOption
            $recipientOption = $form->get('recipientOption')->getData();

            switch ($recipientOption) {
                case 'manual':
                    if (!empty($data['directNumbers'])) {
                        $rawNumbers = preg_split('/[\r\n]+/', $data['directNumbers'], -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($rawNumbers as $number) {
                            $recipients[] = trim($number);
                        }
                    }
                    break;
                case 'group':
                    if ($data['contactGroups']) {
                        foreach ($data['contactGroups'] as $group) {
                            if ($group instanceof ContactGroup) {
                                foreach ($group->getContacts() as $contact) {
                                    $recipients[] = $contact->getPhoneNumber();
                                }
                            }
                        }
                    }
                    break;
                case 'import':
                    $importFile = $form->get('importFile')->getData();
                    if ($importFile) {
                        // IMPORTANT : La logique d'importation doit être ici.
                        // Pour l'exemple, supposons un fichier texte simple avec un numéro par ligne.
                        $fileContent = file_get_contents($importFile->getPathname());
                        $fileNumbers = preg_split('/[\r\n]+/', $fileContent, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($fileNumbers as $number) {
                            $recipients[] = trim($number);
                        }
                    }
                    break;
            }

            // Supprimer les doublons et nettoyer les numéros
            $recipients = array_unique(array_filter($recipients));

            if (empty($recipients)) {
                $this->addFlash('error', 'Veuillez spécifier au moins un destinataire (numéro direct, contact ou groupe).');
                return $this->redirectToRoute('app_send_sms');
            }

            $numberOfRecipients = count($recipients);
            $costPerSms = 1; // Coût par SMS (à adapter)
            $totalCost = $numberOfRecipients * $costPerSms; // Gardé pour information

            // --- Suppression de la vérification du solde ---
            $this->addFlash('info', 'La vérification du solde et le débit des crédits sont actuellement désactivés.');

            // Création de l'entité SmsMessage (pour l'envoi immédiat ou planifié)
            $smsMessage = new SmsMessage();
            $smsMessage->setMessageContent($messageContent);
            $smsMessage->setUser($user); // L'utilisateur connecté est l'expéditeur du message
            $smsMessage->setScheduleAt($scheduleAt); // Sera null si envoi immédiat
            $smsMessage->setCost($totalCost); // Stocker le coût total du message

            // Ajouter les destinataires à l'entité SmsMessage
            foreach ($recipients as $phoneNumber) {
                $smsRecipient = new SmsRecipient();
                $smsRecipient->setPhoneNumber($phoneNumber);
                $smsRecipient->setStatus('pending'); // Statut initial
                $smsMessage->addSmsRecipient($smsRecipient); // Associer le destinataire au message
            }

            if ($scheduleAt === null) { // Envoi immédiat
                $smsMessage->setStatus('sending'); // Statut indiquant qu'il est en cours d'envoi

                $this->entityManager->persist($smsMessage);
                $this->entityManager->flush(); // Persister d'abord pour avoir un ID

                $sentCount = 0;
                $failedCount = 0;

                foreach ($smsMessage->getSmsRecipients() as $smsRecipient) {
                    if ($this->smsSender->sendSms($smsRecipient->getPhoneNumber(), $messageContent)) {
                        $smsRecipient->setStatus('sent');
                        $smsRecipient->setSentAt(new \DateTime());
                        $sentCount++;
                    } else {
                        $smsRecipient->setStatus('failed');
                        $failedCount++;
                    }
                    $this->entityManager->persist($smsRecipient); // Persister les changements de statut
                }
                $this->entityManager->flush(); // Flush final pour les destinataires

                $this->addFlash('success', sprintf('%d SMS transmis à l\'opérateur, %d échecs de transmission.', $sentCount, $failedCount));

            } else { // SMS planifié
                // Mettre le statut 'scheduled'
                $smsMessage->setStatus('scheduled');

                $this->entityManager->persist($smsMessage);
                $this->entityManager->flush(); // Persister le message et ses destinataires avec le statut 'scheduled'

                $this->addFlash('success', sprintf('%d SMS planifiés avec succès pour le %s à %s. Ils seront envoyés automatiquement.', $numberOfRecipients, $scheduleAt->format('d/m/Y'), $scheduleAt->format('H:i')));
            }

            // Rediriger vers une page de rapport ou l'index après l'envoi
            return $this->redirectToRoute('app_contact_index'); // Redirection temporaire
        }

        return $this->render('sms/send_sms.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
