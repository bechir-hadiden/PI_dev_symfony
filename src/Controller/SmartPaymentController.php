<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\User;
use App\Entity\Subscription;
use App\Service\SmartPaymentService;
use App\Service\PaymentAutomationService;
use App\Service\ReservationTimeoutService;
use App\Entity\ReservationTransport;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/smart-paiement')]
class SmartPaymentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SmartPaymentService $smartPaymentService;
    private PaymentAutomationService $automationService;
    private ReservationTimeoutService $timeoutService;
    private \App\Service\FraudService $fraudService;
    private \App\Service\GeoLocationService $geoService;

    public function __construct(
        EntityManagerInterface $entityManager, 
        SmartPaymentService $smartPaymentService,
        PaymentAutomationService $automationService,
        ReservationTimeoutService $timeoutService,
        \App\Service\FraudService $fraudService,
        \App\Service\GeoLocationService $geoService
    ) {
        $this->entityManager = $entityManager;
        $this->smartPaymentService = $smartPaymentService;
        $this->automationService = $automationService;
        $this->timeoutService = $timeoutService;
        $this->fraudService = $fraudService;
        $this->geoService = $geoService;
    }

    /**
     * Interface de test pour le système de paiement intelligent.
     */
    #[Route('/test', name: 'app_smart_payment_test', methods: ['GET'])]
    public function testPage(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        }

        return $this->render('frontoffice/paiement/smart_test.html.twig', [
            'user' => $user,
            'active_subscriptions' => $user ? $user->getSubscriptions() : [],
            'pending_reservations' => $user ? $this->entityManager->getRepository(\App\Entity\ReservationTransport::class)->findBy(['user' => $user, 'status' => 'En attente']) : []
        ]);
    }

    /**
     * Endpoint pour traiter un paiement avec la logique métier + automation.
     */
    #[Route('/process', name: 'app_smart_payment_process', methods: ['POST'])]
    public function process(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        }

        if (!$user) {
            $this->addFlash('error', 'Utilisateur de test non trouvé.');
            return $this->redirectToRoute('app_smart_payment_test');
        }

        // Simuler le lien avec un plan d'abonnement
        $plan = $request->request->get('plan');
        $subscription = null;
        
        if ($plan) {
            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setPlan($plan);
            $subscription->setPrice($plan === 'premium' ? 99.00 : 29.00);
            $subscription->setStartDate(new \DateTime());
            $subscription->setEndDate(new \DateTime('+1 month'));
            $subscription->setStatus('pending_activation'); // Statut provisoire
            
            $this->entityManager->persist($subscription);
        }

        $pays = $request->request->get('pays', 'Tunisie');
        $user->setPays($pays);
        
        $this->entityManager->flush();

        $amount = (float) $request->request->get('amount', 0);
        
        // GESTION IP (Simulation pour le prof/test)
        $clientIp = $request->request->get('simulated_ip') ?: $request->getClientIp();
        
        if ($request->request->get('simulate_suspicious_ip')) {
            $clientIp = '45.133.1.2'; // IP suspecte (Proxy connu)
        }

        if ($request->request->get('force_failure')) {
            $paiement = new Paiement();
            $paiement->setUser($user);
            $paiement->setAmount($amount);
            $paiement->setSubscription($subscription);
            $paiement->setStatus('Refusé');
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setMethodePaiement('Simulation');
            $this->entityManager->persist($paiement);
            $this->entityManager->flush();

            $this->automationService->handlePaymentFailure($paiement);
            
            $this->addFlash('smart_error', [
                'message' => "Échec de paiement forcé : L'abonnement a été suspendu.",
                'score' => 0.85,
                'details' => [
                    ['label' => 'Simulation Interne', 'impact' => '+0.85', 'status' => 'DANGER'],
                    ['label' => 'AI Recommendation', 'impact' => 'RETRY_OPTIMIZED', 'status' => 'WARNING'],
                ],
                'retry_tip' => 'L\'IA conseille de ne pas réessayer avant demain 14:00 (Pic de succès historique détecté).',
                'country' => 'Simulation Lab',
                'ip' => $clientIp
            ]);
            return $this->redirectToRoute('app_smart_payment_test');
        }

        $cardCountry = $request->request->get('card_country');
        $detectedIpCountry = $this->geoService->getCountryByIp($clientIp);

        try {
            $paiement = new Paiement();
            $paiement->setUser($user);
            $paiement->setAmount($amount);
            
            // ANALYSE COMPLÈTE POUR LE TEST (Affichage Prof)
            $analysis = $this->fraudService->getRiskAnalysis($paiement, $clientIp, $detectedIpCountry, $cardCountry);
            $fraudScore = $analysis['score'];
            $riskDetails = $analysis['details'];

            $result = $this->smartPaymentService->processPayment($user, $amount, $clientIp, $cardCountry);
            
            $this->addFlash('smart_success', [
                'message' => 'Paiement effectué avec succès !',
                'score' => $fraudScore,
                'details' => $riskDetails,
                'country' => $detectedIpCountry,
                'ip' => $clientIp
            ]);
            
            /** @var Paiement $paiement */
            $paiement = $result['paiement'];
            $score = $result['score'];

            if ($subscription) {
                $paiement->setSubscription($subscription);
                $this->entityManager->flush();
            }

            if ($paiement->getStatus() === 'Effectué') {
                $this->automationService->handlePaymentSuccess($paiement);
            }

            $scoreMsg = " [🛡️ Rapport de Sécurité - Score: $score]";

            if ($paiement->getStatus() === 'bloqué') {
                $this->addFlash('error', 'SÉCURITÉ : Transaction bloquée par le système anti-fraude.' . $scoreMsg);
            } elseif ($paiement->getStatus() === 'En attente') {
                $this->addFlash('info', 'Paiement en attente de validation administrative (Montant élevé).' . $scoreMsg);
            } else {
                $this->addFlash('success', 'Paiement réussi ! Transaction vérifiée par SmartPay AI.' . $scoreMsg);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_smart_payment_test');
    }

    /**
     * Action Admin : Validation d'un paiement en attente.
     */
    #[Route('/{id}/valider', name: 'app_smart_payment_validate_admin', methods: ['POST'])]
    public function validateAdmin(#[MapEntity(id: 'id')] Paiement $paiement): Response
    {
        try {
            $this->smartPaymentService->validatePaymentByAdmin($paiement);
            $this->automationService->handlePaymentSuccess($paiement);
            $this->addFlash('success', 'Paiement validé par l\'administrateur & Abonnement activé.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_smart_payment_test');
    }

    /**
     * Simuler un échec via l'automatisation.
     */
    #[Route('/{id}/simuler-echec', name: 'app_smart_payment_fail', methods: ['POST'])]
    public function simulateFail(#[MapEntity(id: 'id')] Paiement $paiement): Response
    {
        $this->automationService->handlePaymentFailure($paiement);
        $this->addFlash('warning', 'Échec enregistré. Tentative n°' . $paiement->getAttempts() . '/3 (Email envoyé)');
        
        return $this->redirectToRoute('app_smart_payment_test');
    }

    /**
     * Créer une réservation de test pour simuler le timeout.
     */
    #[Route('/test-reservation', name: 'app_smart_payment_test_res', methods: ['POST'])]
    public function testReservation(Request $request, TransportRepository $transportRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        
        $transport = $transportRepository->findOneBy([]);
        if (!$transport) {
            $this->addFlash('error', 'Aucun transport trouvé pour le test.');
            return $this->redirectToRoute('app_smart_payment_test');
        }

        $res = new ReservationTransport();
        $res->setUser($user);
        $res->setTransport($transport);
        $res->setStatus('En attente');
        
        // Simuler une expiration rapide pour le test (si demandé)
        if ($request->request->get('simulate_old')) {
            $res->setExpiresAt((new \DateTime())->modify('-1 minute'));
        }

        $this->entityManager->persist($res);
        $this->entityManager->flush();

        $this->addFlash('success', 'Réservation #'.$res->getId().' créée. Expire à : ' . $res->getExpiresAt()->format('H:i:s'));
        return $this->redirectToRoute('app_smart_payment_test');
    }
}

