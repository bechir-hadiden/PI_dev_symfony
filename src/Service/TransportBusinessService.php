<?php

namespace App\Service;

use App\Entity\Transport;

/**
 * Service gérant la logique métier avancée pour les transports.
 * Répond au critère : Métier avancé (au moins 3 conditions).
 */
class TransportBusinessService
{
    /**
     * Calcule un score d'efficacité écologique pour un véhicule.
     * Logique basée sur le type de transport et la capacité.
     */
    public function calculateEcoScore(Transport $transport): int
    {
        $score = 50; // Score de base
        $type = $transport->getTransportType() ? $transport->getTransportType()->getNom() : '';

        // Condition 1 : Bonus pour les transports en commun terrestres (Bus/Train)
        if ($type === 'Bus' || $type === 'Train') {
            $score += 20;
        }

        // Condition 2 : Pénalité pour les vols (Avion)
        if ($type === 'Avion') {
            $score -= 30;
        }

        // Condition 3 : Bonus si la capacité est élevée (transport de masse)
        if ($transport->getCapacite() > 50) {
            $score += 15;
        }

        // Condition 4 : Malus si c'est un petit véhicule individuel (Covoiturage avec peu de places)
        if ($type === 'Covoiturage' && $transport->getCapacite() < 5) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calcule le prix final dynamique en fonction de plusieurs facteurs métiers.
     * Répond au critère : Métier avancé (Saison, Voyageurs, Type, Urgence).
     */
    public function calculateDynamicPrice(Transport $transport, int $passengers = 1, ?\DateTime $departureDate = null): array
    {
        $basePrice = $transport->getPrix();
        $finalPrice = $basePrice;
        $appliedRules = [];

        // 1. Condition : Saisonnalité (Saison)
        // Été (Juin, Juillet, Août) : +20% | Hiver (Décembre, Janvier, Février) : -10%
        $month = (int)date('m');
        if (in_array($month, [6, 7, 8])) {
            $finalPrice *= 1.20;
            $appliedRules[] = 'Haute Saison (Été) : +20%';
        } elseif (in_array($month, [12, 1, 2])) {
            $finalPrice *= 0.90;
            $appliedRules[] = 'Basse Saison (Hiver) : -10%';
        }

        // 2. Condition : Nombre de voyageurs (Nombre)
        // Remise Groupe : > 10 passagers -> -15%
        if ($passengers > 10) {
            $finalPrice *= 0.85;
            $appliedRules[] = 'Remise Groupe (>10 pers.) : -15%';
        }

        // 3. Condition : Type de transport (Type)
        // Supplément Luxe pour Avion ou Voiture privée : +10%
        $type = $transport->getTransportType() ? $transport->getTransportType()->getNom() : '';
        if ($type === 'Avion' || $type === 'Voiture privée') {
            $finalPrice *= 1.10;
            $appliedRules[] = 'Supplément Luxe (' . $type . ') : +10%';
        }

        // 4. Condition : Urgence (Urgence)
        // Réservation à moins de 24h : +25%
        if ($departureDate) {
            $now = new \DateTime();
            $diff = $now->diff($departureDate);
            $hours = ($diff->days * 24) + $diff->h;
            if ($hours < 24 && $departureDate > $now) {
                $finalPrice *= 1.25;
                $appliedRules[] = 'Frais d\'Urgence (<24h) : +25%';
            }
        }

        return [
            'base_price' => $basePrice,
            'final_price' => round($finalPrice, 2),
            'total_price' => round($finalPrice * $passengers, 2),
            'rules' => $appliedRules
        ];
    }

    /**
     * Vérifie si un transport est disponible selon l'heure et le jour.
     * Répond au critère : Métier avancé (Heure, Jour, Type, Deadlines techniques).
     */
    public function isAvailable(Transport $transport, string $hour, ?\DateTime $departureDate = null): array
    {
        $type = $transport->getTransportType() ? $transport->getTransportType()->getNom() : '';
        $h = (int)explode(':', $hour)[0];
        $dayNum = (int)date('N'); // 1-7 (Lundi-Dimanche)
        $isWeekend = ($dayNum >= 6);

        // 1. Condition : Heure par type
        if ($type === 'Bus' && ($h < 6 || $h > 22)) {
            return ['status' => false, 'message' => 'Le Bus n\'opère pas entre 22h et 06h.'];
        }
        if ($type === 'Train' && ($h < 5 || $h >= 23)) {
            return ['status' => false, 'message' => 'Le Train ne circule pas entre 23h et 05h.'];
        }
        if ($type === 'Avion' && ($h < 8 || $h > 22)) {
            return ['status' => false, 'message' => 'Pas de vols programmés à cette heure tardive.'];
        }

        // 2. Condition : Bloquage technique Avions si < 2h (Temps avant départ)
        if ($type === 'Avion' && $departureDate) {
            $now = new \DateTime();
            $diff = $now->diff($departureDate);
            $hoursBefore = ($diff->days * 24) + $diff->h;
            if ($hoursBefore < 2 && $departureDate > $now) {
                return ['status' => false, 'message' => 'Trop tard pour l\'enregistrement (minimum 2h avant le vol).'];
            }
        }

        // 3. Condition : Jour (Weekend)
        if ($type === 'Covoiturage' && $isWeekend) {
            return ['status' => false, 'message' => 'Les covoiturages sont réservés aux trajets de semaine.'];
        }

        // 4. Condition : Fréquence réduite le Dimanche (Simulation)
        if ($dayNum === 7 && ($type === 'Bus' || $type === 'Train') && ($h >= 12 && $h <= 14)) {
            return ['status' => false, 'message' => 'Service interrompu le dimanche midi (pause technique).'];
        }

        return ['status' => true, 'message' => 'Transport disponible.'];
    }

    /**
     * Analyse un trajet et détermine son niveau de risque.
     * Répond au critère : Métier avancé (Météo, Heure, Distance, Type).
     */
    public function detectTripRisk(string $meteo, int $heure, float $distance, string $typeTransport): array
    {
        // 1. Condition : Tempête (Danger immédiat)
        if ($meteo === 'tempete') {
            return ['level' => 'Danger', 'message' => 'Trajet dangereux (Tempête)', 'color' => 'red'];
        }

        // 2. Condition : Avion + Vent fort
        if ($typeTransport === 'Avion' && $meteo === 'vent fort') {
            return ['level' => 'High', 'message' => 'Risque élevé (Vents violents pour vol)', 'color' => 'orange'];
        }

        // 3. Condition : Nuit (0h-5h) + Longue distance (>100km)
        if ($heure >= 0 && $heure <= 5 && $distance > 100) {
            return ['level' => 'Medium', 'message' => 'Risque moyen (Visibilité réduite & fatigue)', 'color' => 'orange'];
        }

        // 4. Condition : Bus + Très longue distance (>200km)
        if ($typeTransport === 'Bus' && $distance > 200) {
            return ['level' => 'Medium', 'message' => 'Risque : fatigue conducteur (Longue distance)', 'color' => 'orange'];
        }

        // 5. Par défaut : Trajet sûr
        return ['level' => 'Safe', 'message' => 'Trajet sûr', 'color' => 'green'];
    }
}
