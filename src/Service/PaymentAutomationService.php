<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service gérant les automatismes post-paiement.
 */
class PaymentAutomationService
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private SubscriptionService $subscriptionService;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SubscriptionService $subscriptionService
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Actions en cas de succès : Activation ou création d'abonnement.
     */
    public function handlePaymentSuccess(Paiement $paiement): void
    {
        // 1. Mise à jour du statut
        $paiement->setStatus('Effectué');

        // 2. Logique Abonnement : Délégation au SubscriptionService
        if ($paiement->getSubscription()) {
            $this->subscriptionService->handlePaymentSuccess($paiement);
        }

        // 3. Bonus de fidélité
        $user = $paiement->getUser();
        if ($user) {
            $user->setLoyaltyPoints($user->getLoyaltyPoints() + 10);
        }

        $this->entityManager->flush();
    }

    /**
     * Actions en cas d'échec : Tentatives, Notifications et Blocage.
     */
    public function handlePaymentFailure(Paiement $paiement): void
    {
        $paiement->setStatus('Refusé');
        $paiement->incrementAttempts();
        
        $user = $paiement->getUser();
        $attempts = $paiement->getAttempts();

        $this->sendFailureEmail($user, $paiement);

        // 2. Logique Abonnement : Suspension automatique si lié
        if ($paiement->getSubscription()) {
            $this->subscriptionService->handlePaymentFailure($paiement);
        }

        // 3. Logique de blocage : Si 3 échecs, on verrouille le compte
        if ($attempts >= 3 && $user) {
            $user->setEstBloque(true);
        }

        $this->entityManager->flush();
    }

    /**
     * Envoi d'un email informatif à l'utilisateur.
     */
    private function sendFailureEmail(User $user, Paiement $paiement): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new Email())
            ->from('security@voyageelite.com')
            ->to($user->getEmail())
            ->subject('⚠️ Problème avec votre paiement VoyageElite')
            ->text(sprintf(
                "Bonjour %s,\n\nVotre paiement de %.2f TND a échoué (Tentative %d/3).\n" .
                "En cas de 3 échecs consécutifs, votre compte sera suspendu par mesure de sécurité.",
                $user->getEmail(),
                $paiement->getAmount(),
                $paiement->getAttempts()
            ));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Silencieux pour le test
        }
    }
}
