<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chatbot')]
class ChatbotController extends AbstractController
{
    #[Route('/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = strtolower(trim($data['message'] ?? ''));

        if (empty($message)) {
            return new JsonResponse(['reply' => "Je n'ai pas compris votre message."]);
        }

        $reply = $this->analyzeMessage($message);

        // Sleep to simulate thinking time (feels more natural)
        usleep(500000); // 0.5s

        return new JsonResponse(['reply' => $reply]);
    }

    private function analyzeMessage(string $msg): string
    {
        // Supprimer la ponctuation pour l'analyse
        $cleanMsg = preg_replace('/[[:punct:]]/', ' ', $msg);

        // Mots-clés: Prix & IA
        if ($this->containsAny($cleanMsg, ['prix', 'tarif', 'cher', 'argent', 'cout', 'budget'])) {
            return "Le prix est <b>dynamique</b> sur notre plateforme (IA). Il varie selon la saison (+20% Été), l'urgence (+25% si <24h), ou si vous voyagez en groupe (-15% pour >10 pers.). Pouvez-vous me dire ce que vous cherchez ?";
        }

        // Mots-clés: Météo & Danger
        if ($this->containsAny($cleanMsg, ['meteo', 'danger', 'risque', 'tempete', 'vent', 'securite'])) {
            return "Nous avons une analyse de sécurité en temps réel couplée à l'API Open-Meteo ! ⛈️ Si le trajet est trop risqué (ex: Tempête), notre IA désactivera le voyage ou vous avertira du danger. Utilisez <a href='/transport/recommendation' style='color:var(--gold);text-decoration:underline;'>Smart Advisor</a> pour tester.";
        }

        // Mots-clés: Recommandation
        if ($this->containsAny($cleanMsg, ['recommande', 'conseille', 'avis', 'meilleur', 'ia'])) {
            return "Je peux vous recommander le voyage parfait en fonction de votre budget, l'heure et la météo ! 🧠 Cliquez sur ce lien : <a href='/transport/recommendation' style='color:var(--gold);text-decoration:underline;'>Consulter notre IA Recommandation</a>.";
        }

        // Mots-clés: Réservation
        if ($this->containsAny($cleanMsg, ['reserver', 'billet', 'place', 'complet', 'disponible'])) {
            return "Vous cherchez à réserver ? 🚌 <br>Allez sur l'onglet <a href='/transports' style='color:var(--gold);text-decoration:underline;'>Transports</a>. Attention, les places disponibles se mettent à jour en temps réel et si c'est 'Complet', vous ne pourrez plus réserver.";
        }

        // Mots-clés: Bonjour
        if ($this->containsAny($cleanMsg, ['bonjour', 'salut', 'coucou', 'hey', 'hello'])) {
            return "Bonjour ! 👋 Je suis l'Assistant SmartTrip. Comment puis-je vous aider aujourd'hui ? (Prix, Réservation, Météo ou IA ?)";
        }
        
        // Mots-clés: Merci
        if ($this->containsAny($cleanMsg, ['merci', 'super', 'top', 'genial'])) {
            return "Je vous en prie ! N'hésitez pas si vous avez d'autres questions. 🚀";
        }

        // Fallback
        return "Je suis désolé, je n'ai pas compris. Vous pouvez me poser des questions sur les <b>tarifs</b>, la <b>météo/sécurité</b>, ou notre système de <b>recommandation IA</b> !";
    }

    private function containsAny(string $string, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            // Check for whole word match
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $string)) {
                return true;
            }
        }
        return false;
    }
}
