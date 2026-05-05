<?php

namespace App\Service;

use App\Repository\TransportRepository;

/**
 * Service principal "Smart Advisor" orchestrant l'IA, les API et le Métier.
 */
class SmartAdvisorService
{
    public function __construct(
        private TransportRepository $transportRepo,
        private WeatherApiService   $weatherApi,
        private DistanceApiService  $distanceApi,
        private DelayPredictionService $iaService,
        private TransportBusinessService $pricingEngine
    ) {}

    /**
     * Recommande le meilleur transport basé sur 4 critères métier.
     * Répond aux critères : Métier avancé + API + IA.
     */
    public function getRecommendations(string $depart, string $arrivee, float $budget, int $passengers, string $hour): array
    {
        // 1. Appel API A : Distance & Durée
        $distance = $this->distanceApi->getDistance($depart, $arrivee);
        $durationBase = $this->distanceApi->getDuration($distance);

        // 2. Appel API B : Météo
        $weather = $this->weatherApi->getTravelAdvice();

        // 3. Récupération des transports et application du Métier avancé
        $allTransports = $this->transportRepo->findAll();
        $recommendations = [];

        $isRushHour = $this->checkRushHour($hour);

        foreach ($allTransports as $t) {
            $type = $t->getTransportType()->getNom();
            $score = 100;
            $reasons = [];

            // Condition 1 : Capacité (Obligatoire)
            if ($t->getCapacite() < $passengers) continue;

            // Condition 2 : Budget (Obligatoire)
            $totalPrice = $t->getPrix() * $passengers;
            if ($totalPrice > $budget) continue;

            // Condition 3 : Distance (Métier)
            if ($distance < 5 && ($type === 'Taxi' || $type === 'Covoiturage')) {
                $score += 20;
                $reasons[] = 'Idéal pour courte distance.';
            } elseif ($distance > 300 && $type === 'Avion') {
                $score += 30;
                $reasons[] = 'Le plus rapide pour longue distance.';
            }

            // Condition 4 : Heure de pointe (Métier)
            if ($isRushHour) {
                if ($type === 'Train') {
                    $score += 25;
                    $reasons[] = 'Évite les bouchons en heure de pointe.';
                } elseif ($type === 'Taxi' || $type === 'Bus') {
                    $score -= 30;
                    $reasons[] = 'Risque de trafic important actuellement.';
                }
            }

            // Condition 5 : Météo (API + Métier)
            if ($weather['status'] === 'Danger' && $type !== 'Train') {
                $score -= 50;
                $reasons[] = 'Déconseillé par gros orage.';
            }

            // 4. Calcul de la date de départ pour la logique d'urgence
            $departureDate = new \DateTime($hour);
            if ($departureDate < new \DateTime()) {
                $departureDate->modify('+1 day');
            }
            $now = new \DateTime();
            $diffAtNow = $now->diff($departureDate);
            $hoursBefore = ($diffAtNow->days * 24) + $diffAtNow->h;
            $isVeryUrgent = ($hoursBefore < 3 && $departureDate > $now);

            // 5. Appel Métier Avancé : Disponibilité (Heure, Jour, Type, Temps avant départ)
            $availability = $this->pricingEngine->isAvailable($t, $hour, $departureDate);
            if (!$availability['status']) {
                $score -= 2000; // Bloqué techniquement
            }

            // 6. Logique Urgence Spécifique (< 3h)
            if ($isVeryUrgent && $availability['status']) {
                if ($type === 'Taxi' || $type === 'Voiture privée') {
                    $score += 50;
                    $reasons[] = 'URGENCE : Déploiement immédiat possible.';
                } else {
                    $score -= 40;
                    $reasons[] = 'Déconseillé en urgence (temps d\'attente/enregistrement).';
                }
            }

            // 7. Appel Métier Avancé : Calcul Dynamique du Prix (Saison, Urgence, Voyageurs, Type)
            $pricing = $this->pricingEngine->calculateDynamicPrice($t, $passengers, $departureDate);
            $totalPrice = $pricing['total_price'];

            if ($totalPrice > $budget) continue;

            // Alerte Budget en Urgence
            if ($isVeryUrgent && $budget < 50) {
                $reasons[] = 'ATTENTION : Options limitées par votre budget serré en urgence.';
            }

            // 8. Appel Métier Avancé : Détection de Trajet Risqué (Météo, Heure, Distance, Type)
            // On mappe le statut météo simplifié aux termes demandés
            $meteoTerm = match($weather['status']) {
                'Danger' => 'tempete',
                'Warning' => 'vent fort',
                default => 'normal'
            };
            $hInt = (int)explode(':', $hour)[0];
            $riskAnalysis = $this->pricingEngine->detectTripRisk($meteoTerm, $hInt, $distance, $type);

            // 9. Appel IA : Prédiction de retard
            $prediction = $this->iaService->predictDelay($type, $hour, $weather['status']);

            $recommendations[] = [
                'transport' => $t,
                'availability' => $availability,
                'isVeryUrgent' => $isVeryUrgent,
                'risk' => $riskAnalysis,
                'pricing' => $pricing,
                'totalPrice' => $totalPrice, // gardé pour compatibilité template
                'score' => $score,
                'reasons' => array_merge($reasons, $pricing['rules']),
                'ia_prediction' => $prediction,
                'estimated_duration' => $durationBase + $prediction['minutes']
            ];
        }

        // Tri par score décroissant
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'distance' => $distance,
            'weather' => $weather,
            'results' => $recommendations
        ];
    }

    private function checkRushHour(string $hour): bool
    {
        $h = (int)explode(':', $hour)[0];
        return ($h >= 7 && $h <= 9) || ($h >= 16 && $h <= 18);
    }
    public function getPaymentAdvice(\App\Entity\User $user, float $amount): ?string
    {
        if ($user->getWalletBalance() >= $amount) {
            return "💡 Conseil : Utilisez votre Wallet SmartTrip pour ce paiement de " . number_format($amount, 2) . " DT. Vous gagnerez immédiatement " . number_format($amount * 0.05, 2) . " DT en Cashback !";
        }

        if ($user->getWalletBalance() > 0) {
            return "💡 Conseil : Il vous manque " . number_format($amount - $user->getWalletBalance(), 2) . " DT dans votre Wallet pour bénéficier des 5% de Cashback. Rechargez-le pour économiser !";
        }

        return "💡 Conseil : Le paiement par Wallet vous permet de cumuler des points Elite et du Cashback. Pensez à l'utiliser !";
    }
}
