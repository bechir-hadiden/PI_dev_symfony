<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ModerationService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $n8nWebhookUrl = 'http://localhost:5678/webhook/analyse-avis';

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Envoie le commentaire à n8n pour analyse par Ollama
     * Retourne un tableau avec le statut, le sentiment et les réponses
     */
    public function analyserCommentaire(string $texte): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->n8nWebhookUrl, [
                'json' => [
                    'commentaire' => $texte,
                ],
                'timeout' => 15, // On laisse 15s à la RTX 3050 pour répondre
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            // En cas d'erreur (n8n éteint, etc.), on log et on retourne un statut de sécurité
            $this->logger->error('Erreur ModerationService : ' . $e->getMessage());
            
            return [
                'statut' => 'PENDING',
                'sentiment' => 3,
                'reponse_fr' => 'Merci pour votre avis, il est en cours de modération.',
                'error' => true
            ];
        }
    }
}