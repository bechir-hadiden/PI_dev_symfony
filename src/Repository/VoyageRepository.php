<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    public function findPopulaires(int $limit = 3): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.prix', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findDisponibles(?int $destinationId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->orderBy('v.dateDebut', 'ASC');

        if ($destinationId) {
            $qb->andWhere('v.destinationRel = :did')
                ->setParameter('did', $destinationId);
        }

        return $qb->getQuery()->getResult();
    }

    public function search(string $q): array
    {
        return $this->searchQuery($q)->getResult();
    }

    public function searchQuery(string $q): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.destination LIKE :q OR v.paysDepart LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('v.dateDebut', 'ASC')
            ->getQuery();
    }

    // public function findDisponiblesQuery(?int $destinationId = null): \Doctrine\ORM\Query
    // {
    //     $qb = $this->createQueryBuilder('v')
    //         ->orderBy('v.dateDebut', 'ASC');

    //     if ($destinationId) {
    //         $qb->andWhere('v.destinationRel = :did')
    //             ->setParameter('did', $destinationId);
    //     }

    //     return $qb->getQuery();
    // }

    public function findDisponiblesQuery(?int $destinationId = null)
    {
        $qb = $this->createQueryBuilder('v');

        if ($destinationId) {
            $qb->andWhere('v.destination = :dest')
                ->setParameter('dest', $destinationId);
        }

        return $qb->getQuery();
    }

    public function findForHome(int $limit = 4): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.destinationRel', 'd')
            ->addSelect('d')
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}