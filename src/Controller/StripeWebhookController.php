<?php

namespace App\Controller;

use App\Service\PaymentService;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Webhook Controller pour Stripe.
 * 
 * Stripe envoie des événements POST à cet endpoint quand le statut
 * d'un PaymentIntent change (succès, échec, etc.).
 * 
 * Configuration Stripe Dashboard :
 *   URL Webhook : https://votre-domaine.com/webhook/stripe
 *   Événements : payment_intent.succeeded, payment_intent.payment_failed
 */
class StripeWebhookController extends AbstractController
{
    /**
     * Endpoint webhook Stripe.
     * 
     * Vérifie la signature → identifie le Paiement → déclenche la logique métier.
     * 
     * ⚠️ Cet endpoint ne doit PAS être protégé par le firewall Symfony.
     *    Ajouter dans security.yaml :
     *    firewalls:
     *        webhook:
     *            pattern: ^/webhook
     *            security: false
     */
    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request, PaymentService $paymentService): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        // ── 1. Vérification de la signature ──
        try {
            $event = $paymentService->verifyWebhookSignature($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            // Signature invalide → rejet
            return new Response('⚠️ Signature invalide : ' . $e->getMessage(), 400);
        } catch (\UnexpectedValueException $e) {
            // Payload malformé
            return new Response('⚠️ Payload invalide : ' . $e->getMessage(), 400);
        }

        // ── 2. Extraction du PaymentIntent ──
        $paymentIntent = $event->data->object;
        $paymentIntentId = $paymentIntent->id ?? null;

        if (!$paymentIntentId) {
            return new Response('⚠️ PaymentIntent ID manquant.', 400);
        }

        // ── 3. Recherche du Paiement en base ──
        $paiement = $paymentService->findPaiementByIntentId($paymentIntentId);

        if (!$paiement) {
            // PaymentIntent inconnu (pas créé par notre app) → accepter silencieusement
            return new Response('OK — PaymentIntent non trouvé en base (ignoré).', 200);
        }

        // ── 4. Traitement selon le type d'événement ──
        switch ($event->type) {
            case 'payment_intent.succeeded':
                // ✅ Paiement réussi
                $paymentService->handlePaymentSuccess($paiement);
                return new Response('✅ Paiement traité avec succès.', 200);

            case 'payment_intent.payment_failed':
                // ❌ Paiement échoué
                $paymentService->handlePaymentFailure($paiement);
                return new Response('❌ Échec enregistré (tentative ' . $paiement->getAttempts() . '/3).', 200);

            default:
                // Événement non géré → accepter silencieusement
                return new Response('OK — Événement non géré : ' . $event->type, 200);
        }
    }
}
