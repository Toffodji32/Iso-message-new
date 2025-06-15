<?php
// src/Service/SmsSender.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class SmsSender
{
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;
    private string $smsApiKey;
    private string $smsApiEndpoint;
    private string $smsMode; // Notre nouvel interrupteur

    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient,
        string $smsMode, // Le mode est injecté ici
        string $smsApiKey,
        string $smsApiEndpoint
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->smsMode = $smsMode;
        $this->smsApiKey = $smsApiKey;
        $this->smsApiEndpoint = $smsApiEndpoint;
    }

    /**
     * Envoie un SMS. Agit comme un routeur vers la méthode réelle ou simulée.
     * Votre contrôleur ne voit aucune différence, il reçoit toujours un booléen.
     */
    public function sendSms(string $recipient, string $message): bool
    {
        if ($this->smsMode === 'mock') {
            return $this->simulateSms($recipient, $message);
        }

        // Si le mode n'est pas 'mock', on exécute votre logique d'envoi réel.
        return $this->sendRealSms($recipient, $message);
    }

    /**
     * SIMULE l'envoi de SMS pour les tests et le développement.
     * C'est ici que vous définissez les cas de succès et d'échec.
     */
    private function simulateSms(string $recipient, string $message): bool
    {
        $this->logger->info(sprintf('[SIMULATION] Demande d\'envoi du message "%s" à %s', $message, $recipient));

        // --- CAS D'ÉCHEC SIMULÉ : Message contenant le mot "fail" ---
        if (str_contains(strtolower($message), 'fail')) {
            $this->logger->warning(sprintf('[SIMULATION] Échec forcé pour %s car le message contient "fail".', $recipient));
            return false;
        }

        // --- CAS D'ÉCHEC SIMULÉ : Numéro invalide (on reprend la regex de votre formulaire) ---
        $phoneNumberRegex = '/^\+22901\d{8}$/';
        if (!preg_match($phoneNumberRegex, trim($recipient))) {
            $this->logger->warning(sprintf('[SIMULATION] Échec pour %s car le format du numéro est invalide.', $recipient));
            return false;
        }

        // --- CAS DE SUCCÈS SIMULÉ ---
        $this->logger->info(sprintf('[SIMULATION] Succès de l\'envoi à %s.', $recipient));
        return true;
    }

    /**
     * Envoie un SMS via la VRAIE API.
     * C'est votre code original, simplement déplacé dans une méthode privée.
     */
    private function sendRealSms(string $recipient, string $message): bool
    {
        try {
            $response = $this->httpClient->request('POST', $this->smsApiEndpoint, [
                'json' => [
                    'api_key' => $this->smsApiKey,
                    'to' => $recipient,
                    'message' => $message,
                    'from' => 'IsoMessage',
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                 // Votre logique de vérification de la réponse de l'API reste la même
                 $content = $response->toArray(false);
                 if (isset($content['status']) && $content['status'] === 'success') {
                     $this->logger->info(sprintf('SMS sent to %s via API. Response: %s', $recipient, json_encode($content)));
                     return true;
                 }
                 $this->logger->error(sprintf('API logic failed for %s. Details: %s', $recipient, json_encode($content)));
                 return false;
            }

            $this->logger->error(sprintf('API call failed for %s (HTTP %d).', $recipient, $statusCode));
            return false;

        } catch (Throwable $e) {
            $this->logger->critical(sprintf('SMS API connection error for %s: %s', $recipient, $e->getMessage()));
            return false;
        }
    }
}
