<?php

namespace App\Repository;

use App\Entity\Transport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transport>
 */
class TransportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transport::class);
    }

    /**
     * Find transports filtered by type name (case-insensitive).
     * @return Transport[]
     */
    public function findByTypeName(string $typeName, string $sort = 'id', string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.transportType', 'tt')
            ->where('LOWER(tt.nom) = LOWER(:nom)')
            ->setParameter('nom', $typeName);

        $validSorts = ['id', 'compagnie', 'numero', 'capacite', 'prix'];
        $sortField = in_array($sort, $validSorts) ? $sort : 'id';
        $orderDir = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        return $qb->orderBy('t.' . $sortField, $orderDir)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sorted and optionally searched transports.
     * @return Transport[]
     */
    public function findSortedSearch(string $q = '', string $sort = 'id', string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('t');
        
        $validSorts = ['id', 'compagnie', 'numero', 'capacite', 'prix'];
        $sortField = in_array($sort, $validSorts) ? $sort : 'id';
        $orderDir = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        if (!empty($q)) {
            $qb->where('t.compagnie LIKE :q OR t.numero LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        return $qb->orderBy('t.' . $sortField, $orderDir)
            ->getQuery()
            ->getResult();
    }
}
