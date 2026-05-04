<?php
// src/Repository/SignalementRepository.php

namespace App\Repository;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signalement>
 */
class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signalement::class);
    }

    /**
     * Récupère les signalements en attente
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut = :statut')
            ->setParameter('statut', Signalement::STATUT_EN_ATTENTE)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les signalements par avis
     */
    public function findByAvis(int $avisId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.avis = :avisId')
            ->setParameter('avisId', $avisId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de signalements par statut
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.statut, COUNT(s.id) as count')
            ->groupBy('s.statut');
        
        $results = $qb->getQuery()->getResult();
        
        $counts = [
            Signalement::STATUT_EN_ATTENTE => 0,
            Signalement::STATUT_TRAITE => 0,
            Signalement::STATUT_REJETE => 0
        ];
        
        foreach ($results as $result) {
            $counts[$result['statut']] = $result['count'];
        }
        
        return $counts;
    }

    /**
     * Compte le nombre de signalements pour un avis
     */
    public function countByAvis(int $avisId): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.avis = :avisId')
            ->setParameter('avisId', $avisId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les signalements par motif
     */
    public function findByMotif(string $motif): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.motif = :motif')
            ->setParameter('motif', $motif)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des signalements
     */
    public function getStats(): array
    {
        $total = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $byMotif = $this->createQueryBuilder('s')
            ->select('s.motif, COUNT(s.id) as count')
            ->groupBy('s.motif')
            ->getQuery()
            ->getResult();
        
        $motifStats = [];
        foreach ($byMotif as $item) {
            $motifStats[$item['motif']] = $item['count'];
        }
        
        return [
            'total' => $total,
            'en_attente' => $this->count(['statut' => Signalement::STATUT_EN_ATTENTE]),
            'traites' => $this->count(['statut' => Signalement::STATUT_TRAITE]),
            'rejetes' => $this->count(['statut' => Signalement::STATUT_REJETE]),
            'par_motif' => $motifStats
        ];
    }
}