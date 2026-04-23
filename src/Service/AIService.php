<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService {
    private $client;
    private $apiKey;

    // Mapping : clé française → phrase descriptive anglaise pour le modèle NLI
    private const CATEGORY_LABELS = [
        'Luxe'       => 'This is a luxury hotel, spa, or premium suite travel offer with high-end amenities',
        'Aventure'   => 'This is an adventure, outdoor, hiking, or extreme sports travel offer',
        'Famille'    => 'This is a family-friendly vacation or travel offer with activities for children',
        'Économique' => 'This is a budget-friendly, low-cost, or economical travel deal',
    ];

    // Seuil de confiance minimum — en dessous, on active le fallback PHP
    private const CONFIDENCE_THRESHOLD = 0.35;

    public function __construct(HttpClientInterface $client, string $apiKey) {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function analyzeDescription(string $text): string {
        try {
            $response = $this->client->request('POST', 'https://api-inference.huggingface.co/models/facebook/bart-large-mnli', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                    'parameters' => [
                        'candidate_labels' => array_values(self::CATEGORY_LABELS),
                        'multi_label'      => false,
                    ],
                ],
                'timeout' => 8,
            ]);

            $result = $response->toArray();

            // Vérification structure de la réponse
            if (empty($result['labels']) || empty($result['scores'])) {
                return $this->fallbackAnalysis($text);
            }

            $topLabel = $result['labels'][0];
            $topScore = $result['scores'][0];

            // Si le modèle n'est pas assez confiant → fallback
            if ($topScore < self::CONFIDENCE_THRESHOLD) {
                return $this->fallbackAnalysis($text);
            }

            // Retrouver la clé française depuis la phrase anglaise gagnante
            $category = array_search($topLabel, self::CATEGORY_LABELS);

            return $category !== false ? $category : $this->fallbackAnalysis($text);

        } catch (\Exception $e) {
            // API indisponible, timeout, erreur réseau → fallback PHP
            return $this->fallbackAnalysis($text);
        }
    }

    /**
     * Fallback par mots-clés si l'API Hugging Face est indisponible ou peu confiante.
     * Garantit que le formulaire reste fonctionnel en toute circonstance.
     */
    private function fallbackAnalysis(string $text): string {
        $text = mb_strtolower($text);

        $keywords = [
            'Luxe' => [
                'luxe', 'luxury', 'premium', 'suite', 'spa', 'panoramique',
                'étoile', 'prestige', 'palace', 'vip', 'exclusif', 'haut de gamme',
                'service d\'étage', 'champagne', 'piscine privée', 'villa',
            ],
            'Aventure' => [
                'aventure', 'trek', 'randonnée', 'escalade', 'safari', 'surf',
                'plongée', 'parachute', 'outdoor', 'montagne', 'jungle',
                'expédition', 'nature', 'sport extrême',
            ],
            'Famille' => [
                'famille', 'enfant', 'kids', 'parc', 'animé', 'club',
                'activités', 'baby', 'familial', 'tout compris', 'all inclusive',
                'piscine', 'jeux', 'crèche',
            ],
            'Économique' => [
                'économique', 'budget', 'pas cher', 'low cost', 'promo',
                'réduction', 'discount', 'abordable', 'bon marché', 'offre spéciale',
                'solde', 'tarif réduit', 'deal',
            ],
        ];

        $scores = [];
        foreach ($keywords as $category => $words) {
            $scores[$category] = 0;
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    $scores[$category]++;
                }
            }
        }

        // Retourner la catégorie avec le plus de correspondances
        arsort($scores);
        $best = array_key_first($scores);

        // Si aucun mot-clé trouvé, valeur par défaut
        return ($scores[$best] > 0) ? $best : 'Économique';
    }
}