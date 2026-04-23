<?php

namespace App\Service;

use App\Entity\ReservationTransport;
use App\Repository\ReservationTransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReservationTimeoutService
{
    private EntityManagerInterface $entityManager;
    private ReservationTransportRepository $repository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationTransportRepository $repository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Annule toutes les réservations qui ont dépassé le délai de 15 minutes.
     * @return int Nombre de réservations annulées
     */
    public function cleanupExpiredReservations(): int
    {
        $expired = $this->repository->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.isPaid = :isPaid')
            ->andWhere('r.expiresAt < :now')
            ->setParameter('status', 'En attente')
            ->setParameter('isPaid', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($expired as $reservation) {
            $reservation->setStatus('Annulée');
            $this->logger->info(sprintf('Réservation #%d annulée pour cause de timeout (15 min).', $reservation->getId()));
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Vérifie si une réservation peut encore être payée.
     */
    public function canBePaid(ReservationTransport $reservation): bool
    {
        if ($reservation->isPaid() || $reservation->getStatus() === 'Payée') {
            return false;
        }

        if ($reservation->isExpired() || $reservation->getStatus() === 'Annulée') {
            // Mise à jour automatique si expiration détectée lors de la tentative
            if ($reservation->getStatus() === 'En attente') {
                $reservation->setStatus('Annulée');
                $this->entityManager->flush();
            }
            return false;
        }

        return true;
    }
}
