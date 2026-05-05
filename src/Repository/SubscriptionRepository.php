<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * @return Subscription[] Returns an array of active subscriptions for a user
     */
    public function findActiveSubscriptionsByUser($user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate > :now')
            ->setParameter('user', $user)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
