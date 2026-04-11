<?php
// src/Service/SmartTripChatService.php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Communique avec le webhook N8N "Chat avec Sarah" (Smart Trip).
 *
 * URL N8N  : https://bechir1919.app.n8n.cloud/webhook/sarah-smart-trip/chat
 * Méthode  : POST application/json
 *
 * Corps envoyé à N8N :
 *   { "action": "sendMessage", "sessionId": "...", "chatInput": "..." }
 *
 * Réponse N8N :
 *   { "output": "Réponse de Sarah..." }
 *
 * En fin de parcours, Sarah insère dans sa réponse les balises :
 *   DESTINATION_FINALE, PAYS_FINALE, PAYS_DEPART, BUDGET_FINAL, DUREE_FINALE, PERIODE_FINALE
 * Ce service détecte ces balises pour signaler que le voyage a été créé.
 */
class SmartTripChatService
{
    // Balises de fin de workflow insérées par Sarah quand les 7 infos sont collectées
    private const BALISES_FIN = [
        'DESTINATION_FINALE',
        'PAYS_FINALE',
        'BUDGET_FINAL',
        'DUREE_FINALE',
        'PERIODE_FINALE',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,

        #[Autowire('%env(N8N_WEBHOOK_URL)%')]
        private readonly string $webhookUrl,
    ) {
    }

    /**
     * Envoie un message à Sarah et retourne :
     *   [
     *     'reply'       => string,   // Réponse de Sarah (balises nettoyées)
     *     'voyage_cree' => bool,     // true si N8N vient de créer le voyage
     *   ]
     */
    public function sendMessage(string $message, string $sessionId): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->webhookUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => [
                    'action'     => 'sendMessage',   // Format attendu par chatTrigger N8N
                    'sessionId'  => $sessionId,
                    'chatInput'  => $message,
                ],
                'timeout' => 30, // Sarah peut prendre quelques secondes (Mistral + MySQL)
            ]);

            $data   = $response->toArray();
            $output = $data['output'] ?? $data['reply'] ?? $data['message'] ?? '';

            // Détecter si Sarah a terminé la collecte (balises présentes dans la réponse)
            $voyageCree = $this->detecterFinWorkflow($output);

            // Nettoyer les balises techniques avant d'afficher à l'utilisateur
            $replyPropre = $this->nettoyerBalises($output);

            return [
                'reply'       => $replyPropre,
                'voyage_cree' => $voyageCree,
            ];

        } catch (\Throwable $e) {
            return [
                // 👇 C'est cette ligne qui a été modifiée pour afficher l'erreur
                'reply'       => 'ERREUR TECHNIQUE : ' . $e->getMessage(),
                'voyage_cree' => false,
            ];
        }
    }

    /**
     * Vérifie si toutes les balises de fin sont présentes dans la réponse.
     */
    private function detecterFinWorkflow(string $output): bool
    {
        foreach (self::BALISES_FIN as $balise) {
            if (!str_contains($output, $balise)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Supprime les lignes de balises techniques de la réponse visible par le client.
     * Ex: "DESTINATION_FINALE: Paris" → retiré
     */
    private function nettoyerBalises(string $output): string
    {
        $lignes   = explode("\n", $output);
        $balises  = array_merge(self::BALISES_FIN, ['PAYS_DEPART']);
        $filtrees = [];

        foreach ($lignes as $ligne) {
            $garder = true;
            foreach ($balises as $balise) {
                if (str_starts_with(trim($ligne), $balise)) {
                    $garder = false;
                    break;
                }
            }
            if ($garder) {
                $filtrees[] = $ligne;
            }
        }

        return trim(implode("\n", $filtrees));
    }
}
