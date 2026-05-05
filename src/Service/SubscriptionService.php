<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(EntityManagerInterface $entityManager, SubscriptionRepository $subscriptionRepository)
    {
        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Handles the successful payment and updates a user's subscription.
     */
    public function handlePaymentSuccess(Paiement $paiement): void
    {
        if ($paiement->getStatus() !== 'Effectué') {
            return;
        }

        $user = $paiement->getUser();
        $targetSubscription = $paiement->getSubscription();

        if ($targetSubscription) {
            $this->activateOrExtend($user, $targetSubscription);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Handles payment failure and suspends linked subscription.
     */
    public function handlePaymentFailure(Paiement $paiement): void
    {
        $subscription = $paiement->getSubscription();
        if ($subscription) {
            $subscription->setStatus('suspended');
            $this->entityManager->flush();
        }
    }

    /**
     * Logical core: activates a plan or extends it if already active.
     */
    private function activateOrExtend(User $user, Subscription $subscription): void
    {
        $now = new \DateTime();
        
        // Find if user already has an active subscription for the SAME plan
        $existing = $this->subscriptionRepository->findOneBy([
            'user' => $user,
            'plan' => $subscription->getPlan(),
            'status' => 'active'
        ]);

        if ($existing && $existing->getEndDate() > $now) {
            // EXTEND existing
            $newEndDate = clone $existing->getEndDate();
            $this->addPeriod($newEndDate, $subscription->getPlan());
            $existing->setEndDate($newEndDate);
            
            // Mark the new payment's placeholder subscription as cancelled/merged 
            // OR just link payment to existing and delete placeholder
            // In our current flow, it's cleaner to activate the placeholder and set dates properly
        }
        
        // Setup dates for the subscription being activated
        $subscription->setStartDate($now);
        $endDate = clone $now;
        $this->addPeriod($endDate, $subscription->getPlan());
        $subscription->setEndDate($endDate);
        $subscription->activate();
    }

    private function addPeriod(\DateTime $date, string $plan): void
    {
        if (strtolower($plan) === 'premium') {
            $date->modify('+1 year');
        } else {
            $date->modify('+1 month');
        }
    }
}
