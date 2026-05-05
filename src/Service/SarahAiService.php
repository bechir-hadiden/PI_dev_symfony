<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;

/**
 * Service Sarah AI : Intelligence prédictive pour les réservations.
 */
class SarahAiService
{
    private ReservationRepository $reservationRepository;

    public function __construct(ReservationRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * Analyse les réservations en attente d'un utilisateur et retourne des alertes prédictives.
     */
    public function getNudgesForUser(string $email): array
    {
        $pendingReservations = $this->reservationRepository->findBy([
            'emailClient' => $email,
            'statut' => Reservation::STATUT_EN_ATTENTE
        ]);

        $nudges = [];

        foreach ($pendingReservations as $res) {
            $transport = $res->getTransport();
            $price = $res->getMontantTotal();
            
            // Simulation IA : Hausse de prix si l'avion/train est bientôt complet ou si la résa a > 1h
            $hoursOld = (new \DateTime())->diff($res->getDateReservation())->h + ((new \DateTime())->diff($res->getDateReservation())->days * 24);
            $type = $transport ? $transport->getTransportType()->getNom() : 'Voyage';

            if ($hoursOld >= 0) {
                $predictedIncrease = ($type === 'Avion') ? 25 : 15;
                $newPrice = $price * (1 + ($predictedIncrease / 100));

                $nudges[] = [
                    'id' => $res->getId(),
                    'message' => "Sarah a détecté une forte demande sur les trajets en $type. Le prix de votre réservation #" . $res->getId() . " risque de passer de " . number_format($price, 2) . " DT à " . number_format($newPrice, 2) . " DT sous 24h.",
                    'type' => 'URGENT',
                    'predicted_price' => $newPrice
                ];
            }
        }

        return $nudges;
    }
}
