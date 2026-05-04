<?php

namespace App\Repository;

use App\Entity\Destination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Destination::class);
    }

    public function findWithVoyages(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.voyages', 'v')
            ->addSelect('v')
            ->orderBy('d.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $q): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.nom LIKE :q OR d.pays LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}