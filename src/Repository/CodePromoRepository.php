<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

    public function findAllWithOffre(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.offre', 'o')
            ->addSelect('o')
            ->orderBy('c.dateExpiration', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOffre(int $offreId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.offre = :id')
            ->setParameter('id', $offreId)
            ->getQuery()
            ->getResult();
    }
}