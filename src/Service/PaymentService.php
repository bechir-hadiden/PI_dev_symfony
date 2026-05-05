<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\User;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service centralisé pour la gestion des paiements Stripe.
 * 
 * Responsabilités :
 *  - Créer et récupérer des PaymentIntents Stripe
 *  - Gérer la logique succès/échec
 *  - Incrémenter les tentatives et bloquer l'utilisateur après 3 échecs
 *  - Envoyer des emails de notification en cas d'échec
 *  - Vérifier les signatures webhook
 */
class PaymentService
{
    private string $stripeSecretKey;
    private string $stripeWebhookSecret;
    private EntityManagerInterface $entityManager;
    private PaiementRepository $paiementRepository;
    private SubscriptionService $subscriptionService;
    private MailerInterface $mailer;

    public function __construct(
        string $stripeSecretKey,
        string $stripeWebhookSecret,
        EntityManagerInterface $entityManager,
        PaiementRepository $paiementRepository,
        SubscriptionService $subscriptionService,
        MailerInterface $mailer
    ) {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripeWebhookSecret = $stripeWebhookSecret;
        $this->entityManager = $entityManager;
        $this->paiementRepository = $paiementRepository;
        $this->subscriptionService = $subscriptionService;
        $this->mailer = $mailer;

        Stripe::setApiKey($this->stripeSecretKey);
    }

    // ═══════════════════════════════════════════════════
    //  1. STRIPE API — Création de PaymentIntent
    // ═══════════════════════════════════════════════════

    /**
     * Crée un PaymentIntent Stripe.
     * 
     * @param float  $amount      Montant en TND (sera converti en centimes)
     * @param string $currency    Code devise (ex: 'eur', 'usd')
     * @param string $description Description affichée sur le relevé Stripe
     * @param array  $metadata    Données supplémentaires (user_id, paiement_id, etc.)
     * 
     * @return PaymentIntent L'objet PaymentIntent créé
     */
    public function createPaymentIntent(
        float $amount,
        string $currency = 'eur',
        string $description = 'Paiement VoyageElite',
        array $metadata = []
    ): PaymentIntent {
        return PaymentIntent::create([
            'amount'      => (int) round($amount * 100), // Stripe travaille en centimes
            'currency'    => $currency,
            'description' => $description,
            'metadata'    => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Récupère un PaymentIntent existant par son ID.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    // ═══════════════════════════════════════════════════
    //  2. LOGIQUE MÉTIER — Succès
    // ═══════════════════════════════════════════════════

    /**
     * Traitement d'un paiement réussi :
     *  - Statut → "Effectué"
     *  - Cashback 5% + 10 points de fidélité
     *  - Activation de l'abonnement lié (si applicable)
     */
    public function handlePaymentSuccess(Paiement $paiement): void
    {
        $paiement->setStatus('Effectué');

        $user = $paiement->getUser();
        if ($user) {
            // Détecter si c'est une recharge (pas de réservation ni d'abonnement)
            if (!$paiement->getReservationId() && !$paiement->getSubscription()) {
                // RECHARGE : On ajoute 100% du montant au solde
                $user->setWalletBalance($user->getWalletBalance() + $paiement->getAmount());
            } else {
                // PAIEMENT CLASSIQUE : Cashback 5% + 10 points
                $cashback = $paiement->getAmount() * 0.05;
                $user->setWalletBalance($user->getWalletBalance() + $cashback);
                $user->setLoyaltyPoints($user->getLoyaltyPoints() + 10);
            }
        }

        // Activation abonnement si lié
        if ($paiement->getSubscription()) {
            $this->subscriptionService->handlePaymentSuccess($paiement);
        }

        // Marquer la réservation comme payée si liée
        if ($paiement->getReservationId()) {
            $res = $this->entityManager->getRepository(\App\Entity\ReservationTransport::class)->find($paiement->getReservationId());
            if ($res) {
                $res->setIsPaid(true);
                $res->setStatus('Payée');
            }
        }

        $this->entityManager->flush();
    }

    // ═══════════════════════════════════════════════════
    //  3. LOGIQUE MÉTIER — Échec
    // ═══════════════════════════════════════════════════

    /**
     * Traitement d'un paiement échoué :
     *  - Statut → "Refusé"
     *  - Incrémente le compteur de tentatives
     *  - Si tentatives >= 3 → bloque temporairement l'utilisateur
     *  - Envoie un email de notification
     */
    public function handlePaymentFailure(Paiement $paiement): void
    {
        $paiement->setStatus('Refusé');
        $paiement->incrementAttempts();

        $user = $paiement->getUser();
        $attempts = $paiement->getAttempts();

        // Envoi email de notification d'échec
        if ($user && $user->getEmail()) {
            $this->sendFailureNotification($user, $paiement, $attempts);
        }

        // Blocage temporaire après 3 échecs
        if ($attempts >= 3 && $user) {
            $this->blockUserTemporarily($user);
        }

        $this->entityManager->flush();
    }

    /**
     * Envoie un email d'avertissement à l'utilisateur.
     */
    private function sendFailureNotification(User $user, Paiement $paiement, int $attempts): void
    {
        $remainingAttempts = max(0, 3 - $attempts);

        $subject = $attempts >= 3
            ? '⚠️ Compte temporairement bloqué — VoyageElite'
            : '❌ Échec de paiement — VoyageElite';

        $body = $attempts >= 3
            ? sprintf(
                "Bonjour,\n\nVotre paiement de %.2f TND a échoué pour la %dème fois.\n" .
                "Votre compte a été temporairement bloqué par mesure de sécurité.\n" .
                "Veuillez contacter le support pour débloquer votre compte.\n\n" .
                "Cordialement,\nL'équipe VoyageElite",
                $paiement->getAmount(),
                $attempts
            )
            : sprintf(
                "Bonjour,\n\nVotre paiement de %.2f TND a échoué (tentative %d/3).\n" .
                "Il vous reste %d tentative(s) avant le blocage temporaire de votre compte.\n\n" .
                "Si vous pensez qu'il s'agit d'une erreur, vérifiez vos informations bancaires.\n\n" .
                "Cordialement,\nL'équipe VoyageElite",
                $paiement->getAmount(),
                $attempts,
                $remainingAttempts
            );

        try {
            $email = (new Email())
                ->from('noreply@voyage-elite.com')
                ->to($user->getEmail())
                ->subject($subject)
                ->text($body);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log silently — ne pas bloquer le flux si l'email échoue
        }
    }

    /**
     * Bloque un utilisateur temporairement (rôle restreint).
     */
    private function blockUserTemporarily(User $user): void
    {
        $currentRoles = $user->getRoles();
        if (!in_array('ROLE_BLOCKED', $currentRoles)) {
            $currentRoles[] = 'ROLE_BLOCKED';
            $user->setRoles($currentRoles);
        }
    }

    // ═══════════════════════════════════════════════════
    //  4. WEBHOOK — Vérification de signature
    // ═══════════════════════════════════════════════════

    /**
     * Vérifie la signature d'un webhook Stripe et retourne l'événement.
     *
     * @throws SignatureVerificationException si la signature est invalide
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $sigHeader,
            $this->stripeWebhookSecret
        );
    }

    // ═══════════════════════════════════════════════════
    //  5. RECHERCHE — Par PaymentIntent ID
    // ═══════════════════════════════════════════════════

    /**
     * Retrouve un Paiement en base par son stripePaymentIntentId.
     */
    public function findPaiementByIntentId(string $paymentIntentId): ?Paiement
    {
        return $this->paiementRepository->findOneBy([
            'stripePaymentIntentId' => $paymentIntentId
        ]);
    }
}
