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
}
