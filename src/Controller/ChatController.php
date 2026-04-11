<?php
// src/Controller/ChatController.php

namespace App\Controller;

use App\Service\SmartTripChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    public function __construct(private readonly SmartTripChatService $chatService)
    {
    }

    #[Route('/chat/message', name: 'chat_message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['error' => 'Bad request'], 400);
        }

        $data      = json_decode($request->getContent(), true);
        $message   = trim($data['message']    ?? '');
        $sessionId = trim($data['session_id'] ?? uniqid('st_'));

        if (empty($message)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $result = $this->chatService->sendMessage($message, $sessionId);

        return $this->json($result);
    }
}