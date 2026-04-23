<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de Gestion des Paiements Intelligents (Business Logic Layer)
 * 
 * Ce service implémente les règles métier avancées de VoyageElite :
 * - Frais internationaux de +10% si hors Tunisie.
 * - Mise en attente pour validation admin si montant > 1000 TND.
 * - Blocage automatique du compte après 3 échecs de paiement.
 * - Journalisation complète (Logs) de chaque décision métier.
 */
class SmartPaymentService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private FraudService $fraudService;
    private GeoLocationService $geoService;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, FraudService $fraudService, GeoLocationService $geoService)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->fraudService = $fraudService;
        $this->geoService = $geoService;
    }

    /**
     * Traite un paiement et retourne le paiement + le score de fraude (pour le test).
     */
    public function processPayment(User $user, float $amount, string $clientIp = '127.0.0.1', ?string $cardCountry = null): array
    {
        $this->logger->info("Début du traitement SmartPayment pour : {email}", ['email' => $user->getEmail()]);

        // 1. RÈGLE DE SÉCURITÉ : Vérification du blocage
        if ($user->isEstBloque()) {
            throw new \Exception("Votre compte est bloqué.");
        }

        $detectedIpCountry = $this->geoService->getCountryByIp($clientIp);
        $finalAmount = $amount;
        
        // 2. GÉOLOCALISATION IP MÉTIER (IPinfo logic)
        $this->logger->info("Pays détecté par IP ($clientIp) : $detectedIpCountry");


        if ($detectedIpCountry !== 'Tunisie') {
            $finalAmount = $amount * 1.10;
            $this->logger->warning("FRAIS INTERNATIONAUX (+10%) APPLIQUÉS : $finalAmount TND");
        }

        $paiement = new Paiement();
        $paiement->setUser($user);
        $paiement->setAmount($finalAmount);
        $paiement->setDatePaiement(new \DateTime());
        $paiement->setMethodePaiement('Stripe');

        // CALCUL DU SCORE DE FRAUDE (Incohérence IP vs Carte incluse)
        $fraudScore = $this->fraudService->calculateRiskScore($paiement, $clientIp, $detectedIpCountry, $cardCountry);
        $paiement->setScoreRisque($fraudScore);

        // 4. RÈGLE ANTI-FRAUDE (Blocage si > 0.7)
        if ($fraudScore > 0.7) {
            $paiement->setStatus('bloqué');
            $this->entityManager->persist($paiement);
            $this->entityManager->flush();
            throw new \Exception("Transaction bloquée par le système anti-fraude (Score: $fraudScore)");
        }

        // 5. RÈGLE DU SEUIL : Validation Admin si > 1000 TND
        if ($finalAmount > 1000) {
            $paiement->setStatus('En attente');
        } else {
            $paiement->setStatus('Effectué');
        }

        $this->entityManager->persist($paiement);
        $this->entityManager->flush();

        return ['paiement' => $paiement, 'score' => $fraudScore];
    }

    /**
     * Validation administrative manuelle.
     */
    public function validatePaymentByAdmin(Paiement $paiement): void
    {
        if ($paiement->getStatus() !== 'En attente') {
            throw new \LogicException("Seul un paiement 'En attente' peut être validé manuellement.");
        }

        $paiement->setStatus('Effectué');
        $this->logger->info("ADMIN VALIDATION : Paiement #{id} validé manuellement par un administrateur.", ['id' => $paiement->getId()]);
        
        $this->entityManager->flush();
    }

    /**
     * Gestion des échecs et blocage de compte.
     */
    public function handlePaymentFailure(Paiement $paiement): void
    {
        $paiement->setStatus('Refusé');
        $paiement->incrementAttempts();
        
        $user = $paiement->getUser();
        $this->logger->error("PAYMENT FAILED : Échec pour {email}. Tentative n°{count}", [
            'email' => $user->getEmail(),
            'count' => $paiement->getAttempts()
        ]);

        if ($paiement->getAttempts() >= 3) {
            $user->setEstBloque(true);
            $this->logger->critical("AUTO-BLOCK : Compte {email} verrouillé suite à 3 erreurs de paiement.", ['email' => $user->getEmail()]);
        }

        $this->entityManager->flush();
    }
}
