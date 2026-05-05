<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    /**
     * Compte le nombre de paiements effectués par un utilisateur dans les X dernières minutes.
     */
    public function countRecentPayments($user, int $minutes): int
    {
        $limit = new \DateTime("-{$minutes} minutes");

        return (int) $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.user = :user')
            ->andWhere('p.datePaiement >= :limit')
            ->setParameter('user', $user)
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalRevenue(): float
    {
        return (float) $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.status = :status OR p.status = :status2')
            ->setParameter('status', 'Effectué')
            ->setParameter('status2', 'Completed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
