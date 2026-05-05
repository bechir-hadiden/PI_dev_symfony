<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
use Psr\Log\LoggerInterface;

/**
 * Service de détection de fraude basé sur un score de risque.
 * 
 * Ce service calcule un score de 0 à 1 basé sur :
 * - Montant élevé (+0.4)
 * - Fréquence des transactions (+0.3)
 * - Fiabilité de l'IP (+0.3)
 * - Cohérence Géo-Monétaire (IP vs Carte) (+0.5)
 */
class FraudService
{
    private PaiementRepository $paiementRepository;
    private LoggerInterface $logger;

    // Seuils configurables
    private const AMOUNT_THRESHOLD = 2000.0;
    private const FREQUENCY_MINUTES = 10;
    private const FREQUENCY_LIMIT = 3;
    private const FRAUD_THRESHOLD = 0.7;

    public function __construct(PaiementRepository $paiementRepository, LoggerInterface $logger)
    {
        $this->paiementRepository = $paiementRepository;
        $this->logger = $logger;
    }

    /**
     * Calcule le score de risque et retourne le détail (pour le mode test).
     */
    public function getRiskAnalysis(Paiement $paiement, string $clientIp, ?string $detectedIpCountry = null, ?string $cardCountry = null): array
    {
        $score = 0.0;
        $details = [];

        // 1. Analyse du montant
        if ($paiement->getAmount() > self::AMOUNT_THRESHOLD) {
            $score += 0.4;
            $details[] = ["label" => "Montant élevé (> 2000 TND)", "impact" => "+0.4", "status" => "DANGER"];
        } else {
            $details[] = ["label" => "Montant standard", "impact" => "0.0", "status" => "OK"];
        }

        // 2. Analyse de la fréquence
        $recentCount = $this->paiementRepository->countRecentPayments($paiement->getUser(), self::FREQUENCY_MINUTES);
        if ($recentCount >= self::FREQUENCY_LIMIT) {
            $score += 0.3;
            $details[] = ["label" => "Fréquence d'achat suspecte ($recentCount/10min)", "impact" => "+0.3", "status" => "DANGER"];
        } else {
            $details[] = ["label" => "Fréquence normale", "impact" => "0.0", "status" => "OK"];
        }

        // 3. Analyse de l'IP
        if ($this->isSuspiciousIp($clientIp)) {
            $score += 0.3;
            $details[] = ["label" => "IP détectée sur listes noires (Proxy/VPN)", "impact" => "+0.3", "status" => "DANGER"];
        } else {
            $details[] = ["label" => "IP réputée propre", "impact" => "0.0", "status" => "OK"];
        }

        // 4. SMART DETECTION : Incohérence IP / Pays Carte
        if ($cardCountry && $detectedIpCountry && strtolower($cardCountry) !== strtolower($detectedIpCountry)) {
            $score += 0.5;
            $details[] = [
                "label" => "Incohérence Géo-Monétaire (IP: $detectedIpCountry vs Carte: $cardCountry)",
                "impact" => "+0.5",
                "status" => "DANGER"
            ];
        } else {
            $details[] = ["label" => "Géo-localisation cohérente (IP/Carte)", "impact" => "0.0", "status" => "OK"];
        }

        return [
            'score' => min(1.0, $score),
            'details' => $details
        ];
    }

    /**
     * Calcule le score de risque global d'un paiement.
     */
    public function calculateRiskScore(Paiement $paiement, string $clientIp, ?string $detectedIpCountry = null, ?string $cardCountry = null): float
    {
        $analysis = $this->getRiskAnalysis($paiement, $clientIp, $detectedIpCountry, $cardCountry);
        return $analysis['score'];
    }

    /**
     * Retourne vrai si le paiement est considéré comme frauduleux (> 0.7).
     */
    public function isFraudulent(Paiement $paiement, string $clientIp): bool
    {
        return $this->calculateRiskScore($paiement, $clientIp) > self::FRAUD_THRESHOLD;
    }

    private function isSuspiciousIp(string $ip): bool
    {
        $suspiciousIps = ['123.123.123.123', '1.1.1.1', '45.133.1.2'];
        return in_array($ip, $suspiciousIps);
    }
}
