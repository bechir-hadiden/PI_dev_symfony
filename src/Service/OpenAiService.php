<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OpenAiService
{
    private string $apiKey;

    public function __construct(
        #[Autowire(env: 'GROQ_API_KEY')] string $apiKey
    ) {
        $this->apiKey = $apiKey;
    }

    public function getChatbotResponse(string $userMessage): string
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'gsk_votre_cle_')) {
            return "⚠️ Erreur : La clé API Groq n'est pas configurée dans le fichier .env !";
        }

        $systemPrompt = "Tu es l'assistant virtuel IA de SmartTrip, une plateforme de réservation de transports. "
                      . "Réponds de manière polie, courte (maximum 2 à 3 phrases) et très utile. "
                      . "Si on te pose des questions sur les prix, dis qu'ils sont dynamiques (ils changent selon la saison, la météo et l'urgence).";

        $url = 'https://api.groq.com/openai/v1/chat/completions';
        
        $data = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 150,
            'temperature' => 0.7,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        // C'est ICI qu'on règle définitivement votre problème d'ordinateur (contournement SSL)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "❌ Erreur de connexion réseau : " . $error;
        }

        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            return "❌ Erreur API Groq : " . ($result['error']['message'] ?? 'Erreur inconnue');
        }

        return $result['choices'][0]['message']['content'] ?? "Je n'ai pas pu formuler de réponse.";
    }
}
