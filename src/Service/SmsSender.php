<?php

namespace App\Service;

use Psr\Log\LoggerInterface; // Recommandé pour logger les envois/erreurs

final class SmsSender
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Envoie un SMS à un destinataire donné.
     * Cette méthode est une simulation et devrait être remplacée par l'intégration d'une API de SMS réelle.
     *
     * @param string $recipient Le numéro de téléphone du destinataire.
     * @param string $message Le contenu du message SMS.
     * @return bool Vrai si l'envoi "réussit" (simulé), Faux sinon.
     */
    public function sendSms(string $recipient, string $message): bool
    {
        // --- C'est ici que vous intégreriez votre API d'envoi de SMS réelle ---
        // Par exemple, en utilisant un client HTTP comme Guzzle pour appeler une API tierce.

        // Simulation d'envoi :
        $isSuccessful = true; // Simule une réussite par défaut

        if (str_contains($recipient, 'FAIL')) { // Exemple de condition pour simuler un échec
            $isSuccessful = false;
        }

        if ($isSuccessful) {
            $this->logger->info(sprintf('SMS sent to %s: "%s"', $recipient, $message));
        } else {
            $this->logger->error(sprintf('Failed to send SMS to %s: "%s"', $recipient, $message));
            // Loggez ici les détails réels de l'erreur de l'API externe
        }

        return $isSuccessful;
    }
}
