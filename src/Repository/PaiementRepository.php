<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
<<<<<<< HEAD
=======
 *
 * @method Paiement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Paiement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Paiement[]    findAll()
 * @method Paiement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }
<<<<<<< HEAD

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
=======
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
}
