<?php
// src/Service/SmartTripChatService.php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmartTripChatService
{
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

        #[Autowire('%env(N8N_WEBHOOK_PDF)%')]
        private readonly string $webhookPdf,
    ) {
    }

    public function sendMessage(string $message, string $sessionId): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->webhookUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => [
                    'action'    => 'sendMessage',
                    'sessionId' => $sessionId,
                    'chatInput' => $message,
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return [
                    'reply'       => 'ERREUR HTTP ' . $statusCode,
                    'voyage_cree' => false,
                ];
            }

            $data   = $response->toArray();
            $output = $data['output'] ?? $data['reply'] ?? $data['message'] ?? '';

            $voyageCree = $this->detecterFinWorkflow($output);

            // Extraire les données depuis les balises
            $destination = $this->extraireBalise('DESTINATION_FINALE', $output);
            $pays        = $this->extraireBalise('PAYS_FINALE', $output);
            $budget      = $this->extraireBalise('BUDGET_FINAL', $output);
            $duree       = $this->extraireBalise('DUREE_FINALE', $output);
            $veutPdf     = strtoupper(trim($this->extraireBalise('VEUT_PDF', $output) ?? 'NON'));

            // Nettoyer les balises du texte affiché
            $replyPropre = $this->nettoyerBalises($output);

            // ✅ Générer le lien PDF directement dans Symfony
            if ($voyageCree && $veutPdf === 'OUI' && $destination) {
                $lienPdf = $this->webhookPdf
                    . '?dest='   . urlencode($destination)
                    . '&pays='   . urlencode($pays ?? '')
                    . '&budget=' . urlencode($budget ?? '')
                    . '&duree='  . urlencode($duree ?? '');

                $replyPropre .= "\n\n📄 [Cliquez ici pour télécharger votre devis en PDF](" . $lienPdf . ")";
            }

            return [
                'reply'       => $replyPropre,
                'voyage_cree' => $voyageCree,
            ];

        } catch (\Throwable $e) {
            return [
                'reply'       => 'ERREUR TECHNIQUE : ' . $e->getMessage(),
                'voyage_cree' => false,
            ];
        }
    }

    private function extraireBalise(string $balise, string $output): ?string
    {
        if (preg_match('/' . $balise . '\s*:\s*(.+)/i', $output, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function detecterFinWorkflow(string $output): bool
    {
        foreach (self::BALISES_FIN as $balise) {
            if (!str_contains($output, $balise)) {
                return false;
            }
        }
        return true;
    }

    private function nettoyerBalises(string $output): string
    {
        $lignes  = explode("\n", $output);
        $balises = array_merge(self::BALISES_FIN, ['PAYS_DEPART', 'VEUT_PDF']);
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