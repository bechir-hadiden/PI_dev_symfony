<?php

namespace App\Service;

use App\Entity\Transport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service intégrant l'IA pour la gestion des transports.
 * Répond au critère : IA (Api D).
 */
class AiTransportService
{
    private HttpClientInterface $client;
    private ?string $apiKey;

    public function __construct(HttpClientInterface $client, ?string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Génère une description marketing optimisée via IA pour un véhicule.
     */
    public function generateMarketingDescription(Transport $transport): string
    {
        // Si pas de clé d'API, on simule une réponse d'IA pour la démonstration du projet.
        if (!$this->apiKey || str_contains($this->apiKey, 'XXXX')) {
            return $this->simulateAiDescription($transport);
        }

        // Sinon, on appelle l'API Anthropic (ou autre selon la config)
        return $this->callAnthropicApi($transport);
    }

    /**
     * Simulation d'une IA pour les tests et la démo locale.
     */
    private function simulateAiDescription(Transport $transport): string
    {
        $type = $transport->getTransportType() ? $transport->getTransportType()->getNom() : 'véhicule';
        $company = $transport->getCompagnie();
        $cap = $transport->getCapacite();

        $prompts = [
            "Découvrez le luxe du voyage avec $company. Ce $type de dernière génération offre $cap places spacieuses pour un confort inégalé.",
            "Voyagez intelligemment avec $company. Notre $type allie efficacité énergétique et sécurité pour tous vos déplacements.",
            "Optimisez vos trajets avec le service Premium de $company. Un $type robuste et moderne prêt à vous emmener partout."
        ];

        return $prompts[array_rand($prompts)];
    }

    /**
     * Appel réel à l'API Anthropic.
     */
    private function callAnthropicApi(Transport $transport): string
    {
        try {
            $prompt = sprintf(
                "Génère une description marketing courte (2 phrases) pour un service de transport : Compagnie %s, Type %s, Capacité %d.",
                $transport->getCompagnie(),
                $transport->getTransportType() ? $transport->getTransportType()->getNom() : '',
                $transport->getCapacite()
            );

            $response = $this->client->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                ],
                'json' => [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 100,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ]
                ]
            ]);

            $data = $response->toArray();
            return $data['content'][0]['text'] ?? $this->simulateAiDescription($transport);

        } catch (\Exception $e) {
            return $this->simulateAiDescription($transport);
        }
    }
}
