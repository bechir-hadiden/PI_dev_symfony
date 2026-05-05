<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatController extends AbstractController
{
    /**
     * Route appelée par le JavaScript (fetch('/api/chatbot'))
     */
    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function askChatbot(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        // 1. Récupération du message envoyé depuis le front-end
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? null;

        if (!$userMessage) {
            return new JsonResponse(['reply' => "Veuillez saisir un message."], 400);
        }

        try {
            /** * 2. URL du Webhook n8n 
             * Note : Utilise 'webhook' (Production) si ton workflow est "Active".
             * Utilise 'webhook-test' si tu cliques sur "Execute Workflow" manuellement.
             */
            $n8nWebhookUrl = 'http://127.0.0.1:5678/webhook/chatbot-voyage';

            // 3. Envoi de la requête à n8n
            $response = $httpClient->request('POST', $n8nWebhookUrl, [
                'json' => [
                    'message' => $userMessage,
                    'user'    => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'Anonyme'
                ],
                // Augmentation du temps d'attente à 120s pour laisser l'IA réfléchir
                'timeout' => 120, 
            ]);

            // 4. Extraction de la réponse
            $content = $response->toArray();
            
            /**
             * Selon ton workflow, l'IA Agent renvoie souvent la réponse dans 'output'.
             * Si tu reçois une liste d'items, on prend le premier ou le champ spécifié.
             */
            $botReply = $content['output'] ?? $content[0]['output'] ?? "Désolé, je ne parviens pas à formuler une réponse.";

            return new JsonResponse([
                'reply' => $botReply
            ]);

        } catch (\Exception $e) {
            // En cas de timeout ou de coupure de n8n
            return new JsonResponse([
                'reply' => "Le service d'IA a mis trop de temps à répondre ou n8n est déconnecté. (Détail : " . $e->getMessage() . ")"
            ], 500);
        }
    }
}