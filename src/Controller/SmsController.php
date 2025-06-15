<?php

namespace App\Controller;

use App\Form\SmsMessageTypeForm;
use App\Entity\Contact; // Assurez-vous que Contact est bien utilisé si nécessaire
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
use App\Entity\User; // NOUVEAU : Importez votre entité User ici

final class SmsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SmsSender $smsSender;
    private Security $security;
    private int $maxSmsLimit; // Nouvelle propriété pour la limite
    private int $costPerSms;  // Nouvelle propriété pour le coût unitaire

    public function __construct(
        EntityManagerInterface $entityManager,
        SmsSender $smsSender,
        Security $security,
        int $maxSmsLimit, // Injectez la limite
        int $costPerSms   // Injectez le coût unitaire
    ) {
        $this->entityManager = $entityManager;
        $this->smsSender = $smsSender;
        $this->security = $security;
        $this->maxSmsLimit = $maxSmsLimit;
        $this->costPerSms = $costPerSms;
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

            /** @var User $user */ // CORRECTION : Type-hinting pour assurer que $user est bien une instance de App\Entity\User
            $user = $this->security->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour envoyer des SMS.');
                return $this->redirectToRoute('app_login');
            }

            // Récupération des destinataires (logique existante)
            $recipients = [];
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
                        $fileContent = file_get_contents($importFile->getPathname());
                        $fileNumbers = preg_split('/[\r\n]+/', $fileContent, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($fileNumbers as $number) {
                            $recipients[] = trim($number);
                        }
                    }
                    break;
            }

            $recipients = array_unique(array_filter($recipients));
            $numberOfRecipients = count($recipients);

            if (empty($recipients)) {
                $this->addFlash('error', 'Veuillez spécifier au moins un destinataire (numéro direct, contact ou groupe).');
                return $this->redirectToRoute('app_send_sms');
            }

            // --- VÉRIFICATION DE LA LIMITE DE SMS ---
            if ($numberOfRecipients > $this->maxSmsLimit) {
                $this->addFlash('error', sprintf('Vous ne pouvez envoyer qu\'un maximum de %d SMS par opération. Vous avez %d destinataires.', $this->maxSmsLimit, $numberOfRecipients));
                return $this->redirectToRoute('app_send_sms');
            }

            // Calcul du coût total
            $totalCost = $numberOfRecipients * $this->costPerSms;

            // --- VÉRIFICATION ET DÉBIT DU SOLDE DE L'UTILISATEUR ---
            // La méthode deductSmsCredits est appelée ici, elle est maintenant reconnue grâce au type-hinting
            if (!$user->deductSmsCredits($totalCost)) {
                $this->addFlash('error', sprintf('Solde insuffisant. Vous avez %d crédits, mais %d sont nécessaires pour envoyer %d SMS.', $user->getSmsCredits(), $totalCost, $numberOfRecipients));
                return $this->redirectToRoute('app_send_sms');
            }

            // Persister l'utilisateur après la déduction du solde
            $this->entityManager->persist($user);
            $this->entityManager->flush(); // Applique le changement de solde immédiatement

            // Création du message SMS et des destinataires
            $smsMessage = new SmsMessage();
            $smsMessage->setMessageContent($messageContent);
            $smsMessage->setUser($user);
            $smsMessage->setScheduleAt($scheduleAt);
            $smsMessage->setCost($totalCost); // Enregistre le coût total

            foreach ($recipients as $phoneNumber) {
                $smsRecipient = new SmsRecipient();
                $smsRecipient->setPhoneNumber($phoneNumber);
                $smsRecipient->setStatus('pending'); // Ou un statut initial approprié
                $smsMessage->addSmsRecipient($smsRecipient);
            }

            // Gestion de l'envoi immédiat ou planifié
            if ($scheduleAt === null) {
                $smsMessage->setStatus('sending'); // Statut pour l'envoi immédiat

                $this->entityManager->persist($smsMessage);
                $this->entityManager->flush(); // Persiste le message et les destinataires

                $sentCount = 0;
                $failedCount = 0;

                // Envoi réel via le service SmsSender
                foreach ($smsMessage->getSmsRecipients() as $smsRecipient) {
                    if ($this->smsSender->sendSms($smsRecipient->getPhoneNumber(), $messageContent)) {
                        $smsRecipient->setStatus('sent');
                        $smsRecipient->setSentAt(new \DateTime());
                        $sentCount++;
                    } else {
                        $smsRecipient->setStatus('failed');
                        $failedCount++;
                    }
                    $this->entityManager->persist($smsRecipient);
                }
                $this->entityManager->flush(); 

                $this->addFlash('success', sprintf('%d SMS envoyés, %d échecs de transmission (coût total : %d crédits).', $sentCount, $failedCount, $totalCost));

            } else {
                $smsMessage->setStatus('scheduled'); // Statut pour l'envoi planifié

                $this->entityManager->persist($smsMessage);
                $this->entityManager->flush(); // Persiste le message et les destinataires
                $this->addFlash('success', sprintf('%d SMS planifiés avec succès pour le %s à %s (coût total : %d crédits). Ils seront envoyés automatiquement.', $numberOfRecipients, $scheduleAt->format('d/m/Y'), $scheduleAt->format('H:i'), $totalCost));
            }

            return $this->redirectToRoute('app_send_sms');
        }

        return $this->render('sms/send_sms.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
