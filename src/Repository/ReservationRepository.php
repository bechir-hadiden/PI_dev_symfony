<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Récupère les réservations par email
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.email = :email')
            ->setParameter('email', $email)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations confirmées
     */
    public function findConfirmed(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations annulées
     */
    public function findCancelled(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CANCELLED)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations par destination
     */
    public function findByDestination(string $destination): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.destination = :destination')
            ->setParameter('destination', $destination)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ========== NOUVELLES MÉTHODES POUR L'ADMIN ==========

    /**
     * Récupère les réservations en attente
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', Reservation::STATUS_PENDING)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations terminées
     */
    public function findCompleted(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', Reservation::STATUS_COMPLETED)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de réservations par statut
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->groupBy('r.status');
        
        $results = $qb->getQuery()->getResult();
        
        $counts = [
            Reservation::STATUS_PENDING => 0,
            Reservation::STATUS_CONFIRMED => 0,
            Reservation::STATUS_CANCELLED => 0,
            Reservation::STATUS_COMPLETED => 0
        ];
        
        foreach ($results as $result) {
            $counts[$result['status']] = $result['count'];
        }
        
        return $counts;
    }

    /**
     * Calcule le chiffre d'affaires total
     */
    public function getTotalRevenue(): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.price * r.numberOfPassengers)')
            ->where('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CONFIRMED);
        
        return (float) $qb->getQuery()->getSingleScalarResult() ?: 0;
    }

    /**
     * Calcule le chiffre d'affaires par mois (12 derniers mois)
     */
    public function getMonthlyRevenue(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUBSTRING(r.reservationDate, 1, 7) as month, SUM(r.price * r.numberOfPassengers) as revenue')
            ->where('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults(12);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les destinations les plus populaires
     */
    public function getTopDestinations(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.destination, COUNT(r.id) as count')
            ->where('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->groupBy('r.destination')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les compagnies aériennes les plus utilisées
     */
    public function getTopAirlines(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.airline, COUNT(r.id) as count')
            ->where('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->groupBy('r.airline')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les réservations pour une période donnée
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reservationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations à venir (départ dans le futur)
     */
    public function findUpcoming(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.departureTime > :now')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('now', $now)
            ->setParameter('statuses', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PENDING])
            ->orderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations passées (départ dans le passé)
     */
    public function findPast(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.departureTime < :now')
            ->andWhere('r.status != :cancelled')
            ->setParameter('now', $now)
            ->setParameter('cancelled', Reservation::STATUS_CANCELLED)
            ->orderBy('r.departureTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations sans billet électronique
     */
    public function findWithoutBoardingPass(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.boardingPassFile IS NULL')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques des réservations par mois
     */
    public function getMonthlyStats(int $months = 12): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUBSTRING(r.reservationDate, 1, 7) as month')
            ->addSelect('COUNT(r.id) as total')
            ->addSelect('SUM(CASE WHEN r.status = :confirmed THEN 1 ELSE 0 END) as confirmed')
            ->addSelect('SUM(CASE WHEN r.status = :cancelled THEN 1 ELSE 0 END) as cancelled')
            ->addSelect('SUM(CASE WHEN r.status = :pending THEN 1 ELSE 0 END) as pending')
            ->addSelect('SUM(r.price * r.numberOfPassengers) as revenue')
            ->setParameter('confirmed', Reservation::STATUS_CONFIRMED)
            ->setParameter('cancelled', Reservation::STATUS_CANCELLED)
            ->setParameter('pending', Reservation::STATUS_PENDING)
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults($months);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche avancée des réservations
     */
    public function advancedSearch(array $criteria): array
    {
        $qb = $this->createQueryBuilder('r');
        
        if (!empty($criteria['nomClient'])) {
            $qb->andWhere('r.nomClient LIKE :nom')
               ->setParameter('nom', '%' . $criteria['nomClient'] . '%');
        }
        
        if (!empty($criteria['email'])) {
            $qb->andWhere('r.email LIKE :email')
               ->setParameter('email', '%' . $criteria['email'] . '%');
        }
        
        if (!empty($criteria['destination'])) {
            $qb->andWhere('r.destination LIKE :destination')
               ->setParameter('destination', '%' . $criteria['destination'] . '%');
        }
        
        if (!empty($criteria['status'])) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $criteria['status']);
        }
        
        if (!empty($criteria['dateFrom'])) {
            $qb->andWhere('r.reservationDate >= :dateFrom')
               ->setParameter('dateFrom', $criteria['dateFrom']);
        }
        
        if (!empty($criteria['dateTo'])) {
            $qb->andWhere('r.reservationDate <= :dateTo')
               ->setParameter('dateTo', $criteria['dateTo']);
        }
        
        if (!empty($criteria['minPrice'])) {
            $qb->andWhere('r.price >= :minPrice')
               ->setParameter('minPrice', $criteria['minPrice']);
        }
        
        if (!empty($criteria['maxPrice'])) {
            $qb->andWhere('r.price <= :maxPrice')
               ->setParameter('maxPrice', $criteria['maxPrice']);
        }
        
        return $qb->orderBy('r.reservationDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}