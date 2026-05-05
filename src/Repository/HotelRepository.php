<?php

namespace App\Repository;

use App\Entity\Hotel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hotel>
 */
class HotelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotel::class);
    }

    /**
     * Returns hotels with their images pre-loaded (avoids N+1 queries).
     */
    public function findAllWithImages(): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.images', 'i')
            ->addSelect('i')
            ->leftJoin('h.amenities', 'a')
            ->addSelect('a')
            ->orderBy('h.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithDetails(int $id): ?Hotel
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.images', 'i')
            ->addSelect('i')
            ->leftJoin('h.amenities', 'a')
            ->addSelect('a')
            ->leftJoin('h.roomTypes', 'r')
            ->addSelect('r')
            ->where('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchHotels(string $query): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.images', 'i')
            ->addSelect('i')
            ->where('h.name LIKE :q OR h.city LIKE :q OR h.country LIKE :q OR h.location LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('h.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find hotels with optional text search and price range filters.
     */
    public function findWithFilters(string $query, string $priceMin, string $priceMax): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.images', 'i')
            ->addSelect('i')
            ->leftJoin('h.amenities', 'a')
            ->addSelect('a');

        if ($query !== '') {
            $qb->andWhere('h.name LIKE :q OR h.city LIKE :q OR h.country LIKE :q OR h.location LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($priceMin !== '' && is_numeric($priceMin)) {
            $qb->andWhere('h.pricePerNight >= :min')
               ->setParameter('min', (float) $priceMin);
        }

        if ($priceMax !== '' && is_numeric($priceMax)) {
            $qb->andWhere('h.pricePerNight <= :max')
               ->setParameter('max', (float) $priceMax);
        }

        return $qb->orderBy('h.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
