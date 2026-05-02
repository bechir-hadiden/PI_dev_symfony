<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private HttpClientInterface $httpClient;
    private array $availableLanguages = ['fr', 'en', 'es', 'ar', 'de', 'it'];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Traduit un texte d'une langue à une autre
     */
    public function translate(string $text, string $targetLang = 'fr', string $sourceLang = 'auto'): ?array
    {
        if (empty(trim($text))) {
            return null;
        }

        // Limiter la longueur du texte
        $text = substr($text, 0, 500);

        // Liste des URLs de fallback
        $urls = [
            'https://translate.argosopentech.com',
            'https://libretranslate.com',
            'https://translate.terraprint.co'
        ];

        foreach ($urls as $url) {
            try {
                $response = $this->httpClient->request('POST', $url . '/translate', [
                    'json' => [
                        'q' => $text,
                        'source' => $sourceLang,
                        'target' => $targetLang,
                        'format' => 'text'
                    ],
                    'timeout' => 10,
                    'headers' => [
                        'User-Agent' => 'SmartTrip/1.0'
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();
                    
                    return [
                        'original' => $text,
                        'translated' => $data['translatedText'] ?? null,
                        'source_lang' => $data['detectedLanguage']['language'] ?? $sourceLang,
                        'target_lang' => $targetLang
                    ];
                }
            } catch (\Exception $e) {
                // Continuer avec l'URL suivante
                continue;
            }
        }

        // Fallback: traduction simple via une API alternative (mock pour test)
        return $this->simpleTranslate($text, $targetLang);
    }

    /**
     * Traduction simple (fallback)
     */
    private function simpleTranslate(string $text, string $targetLang): array
    {
        $translated = $text;
        
        // Mots communs (fallback)
        $commonWords = [
            'bonjour' => ['en' => 'hello', 'es' => 'hola', 'ar' => 'مرحبا'],
            'merci' => ['en' => 'thank you', 'es' => 'gracias', 'ar' => 'شكرا'],
            'super' => ['en' => 'great', 'es' => 'genial', 'ar' => 'رائع'],
            'voyage' => ['en' => 'trip', 'es' => 'viaje', 'ar' => 'رحلة'],
            'bien' => ['en' => 'good', 'es' => 'bien', 'ar' => 'جيد'],
            'excellent' => ['en' => 'excellent', 'es' => 'excelente', 'ar' => 'ممتاز'],
        ];

        foreach ($commonWords as $word => $translations) {
            if (stripos($text, $word) !== false && isset($translations[$targetLang])) {
                $translated = str_ireplace($word, $translations[$targetLang], $translated);
            }
        }

        return [
            'original' => $text,
            'translated' => $translated,
            'source_lang' => 'fr',
            'target_lang' => $targetLang,
            'is_fallback' => true
        ];
    }

    public function getAvailableLanguages(): array
    {
        return $this->availableLanguages;
    }
}