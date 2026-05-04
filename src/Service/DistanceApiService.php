<?php

namespace App\Service;

/**
 * Service pour calculer la distance entre deux points (API A).
 */
class DistanceApiService
{
    /**
     * Calcule la distance en km entre deux villes.
     * Pour une démo, on simule un appel API ou on utilise une logique de base.
     */
    public function getDistance(string $depart, string $arrivee): float
    {
        // Dans un cas réel, on appellerait Google Maps ou OpenRouteService ici.
        // Simulation de distance pour la démo :
        $distances = [
            'Tunis-Hammamet' => 65.0,
            'Tunis-Sousse' => 140.0,
            'Tunis-Bizerte' => 66.0,
            'Tunis-Monastir' => 165.0,
            'Tunis-Djerba' => 500.0,
        ];

        $key = ucfirst(strtolower($depart)) . '-' . ucfirst(strtolower($arrivee));
        $revKey = ucfirst(strtolower($arrivee)) . '-' . ucfirst(strtolower($depart));

        return $distances[$key] ?? $distances[$revKey] ?? 50.0; // 50km par défaut
    }

    public function getDuration(float $distance): int
    {
        // Estimation simple : 1.2 min par km
        return (int)($distance * 1.2);
    }
}
