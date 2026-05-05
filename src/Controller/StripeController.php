<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour les paiements via Stripe.
 * 
 * Routes :
 *  - POST /api/stripe/create-payment-intent   → Crée un PaymentIntent
 *  - GET  /paiement/stripe-checkout/{id}       → Page de checkout Stripe Elements
 */
#[Route('/stripe')]
class StripeController extends AbstractController
{
    /**
     * API : Crée un PaymentIntent Stripe + un Paiement en base de données.
     * 
     * Body JSON attendu :
     * {
     *   "amount": 99.99,
     *   "currency": "eur",           // optionnel, défaut: eur
     *   "description": "...",        // optionnel
     *   "subscription_id": 5,        // optionnel
     *   "nom": "Doe",
     *   "prenom": "John",
     *   "email": "john@example.com",
     *   "telephone": "12345678"
     * }
     * 
     * Retourne :
     * {
     *   "clientSecret": "pi_xxx_secret_xxx",
     *   "paiementId": 42
     * }
     */
    #[Route('/create-payment-intent', name: 'api_stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(
        Request $request,
        PaymentService $paymentService,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // --- Récupérer l'utilisateur ---
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Aucun utilisateur trouvé.'], 400);
        }

        // --- Parser le body JSON ---
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            // Fallback : données de formulaire
            $data = $request->request->all();
        }

        $amount = (float) ($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'eur';
        $description = $data['description'] ?? 'Paiement VoyageElite';

        if ($amount <= 0) {
            return new JsonResponse(['error' => 'Le montant doit être supérieur à zéro.'], 400);
        }

        // --- Vérifier si l'utilisateur est bloqué ---
        if (in_array('ROLE_BLOCKED', $user->getRoles())) {
            return new JsonResponse([
                'error' => 'Votre compte est temporairement bloqué suite à des échecs de paiement répétés. Contactez le support.'
            ], 403);
        }

        // --- Subscription optionnelle ---
        $subscription = null;
        $subscriptionId = $data['subscription_id'] ?? null;
        if ($subscriptionId) {
            $subscription = $subscriptionRepository->findOneBy([
                'id' => (int) $subscriptionId,
                'user' => $user,
            ]);
        }

        try {
            // --- Créer le PaymentIntent Stripe ---
            $paymentIntent = $paymentService->createPaymentIntent(
                $amount,
                $currency,
                $description,
                [
                    'user_id' => $user->getId(),
                    'user_email' => $user->getEmail(),
                ]
            );

            // --- Créer le Paiement en base (statut "En attente") ---
            $paiement = new Paiement();
            $paiement->setUser($user);
            $paiement->setAmount($amount);
            $paiement->setMethodePaiement('Stripe');
            $paiement->setStatus('En attente');
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setStripePaymentIntentId($paymentIntent->id);
            $paiement->setSubscription($subscription);

            // Billing info
            $paiement->setNom($data['nom'] ?? null);
            $paiement->setPrenom($data['prenom'] ?? null);
            $paiement->setEmail($data['email'] ?? null);
            $paiement->setTelephone($data['telephone'] ?? null);

            $entityManager->persist($paiement);
            $entityManager->flush();

            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
                'paiementId' => $paiement->getId(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur Stripe : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Page de checkout avec Stripe Elements.
     * Affiche le formulaire de carte bancaire sécurisé.
     */
    #[Route('/checkout/{id}', name: 'app_stripe_checkout', methods: ['GET'])]
    public function checkout(
        Paiement $paiement,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérification de sécurité
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        if (!$user || $paiement->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }

        if ($paiement->getStatus() !== 'En attente') {
            $this->addFlash('error', 'Ce paiement a déjà été traité.');
            return $this->redirectToRoute('app_user_paiement_index');
        }

        return $this->render('frontoffice/paiement/stripe_checkout.html.twig', [
            'paiement' => $paiement,
            'stripe_public_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '',
        ]);
    }

    /**
     * Endpoint appelé par le JS après confirmation réussie côté client.
     * Vérifie le statut du PaymentIntent auprès de Stripe.
     */
    #[Route('/confirm/{id}', name: 'api_stripe_confirm_payment', methods: ['POST'])]
    public function confirmPayment(
        Paiement $paiement,
        PaymentService $paymentService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$paiement->getStripePaymentIntentId()) {
            return new JsonResponse(['error' => 'Pas de PaymentIntent lié.'], 400);
        }

        try {
            $intent = $paymentService->retrievePaymentIntent($paiement->getStripePaymentIntentId());

            if ($intent->status === 'succeeded') {
                $paymentService->handlePaymentSuccess($paiement);
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Paiement confirmé avec succès !',
                    'redirect' => $this->generateUrl('app_user_paiement_index'),
                ]);
            } else {
                $paymentService->handlePaymentFailure($paiement);
                return new JsonResponse([
                    'status' => 'failed',
                    'message' => 'Le paiement a échoué. Tentative ' . $paiement->getAttempts() . '/3.',
                    'attempts' => $paiement->getAttempts(),
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
