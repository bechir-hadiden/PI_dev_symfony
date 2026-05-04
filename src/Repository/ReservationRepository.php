<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Transport;
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
     * Find all reservations for a given transport.
     * @return Reservation[]
     */
    public function findByTransport(Transport $transport): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.transport = :transport')
            ->setParameter('transport', $transport)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations by email (FrontOffice user view).
     * @return Reservation[]
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.emailClient = :email')
            ->setParameter('email', $email)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
