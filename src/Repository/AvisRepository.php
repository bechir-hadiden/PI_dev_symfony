<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    // ========== MÉTHODES POUR LES STATISTIQUES ==========

    /**
     * Calcule la note moyenne des avis approuvés
     */
    public function getAverageNote(): float
    {
        $qb = $this->createQueryBuilder('a')
            ->select('AVG(a.note)')
            ->where('a.status = :status')
            ->setParameter('status', Avis::STATUS_APPROVED);
        
        return (float) $qb->getQuery()->getSingleScalarResult() ?: 0;
    }

    /**
     * Récupère la distribution des notes (combien de 1, 2, 3, 4, 5)
     */
    public function getNotesDistribution(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.note, COUNT(a.id) as count')
            ->where('a.status = :status')
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->groupBy('a.note')
            ->orderBy('a.note', 'ASC');
        
        $results = $qb->getQuery()->getResult();
        
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = 0;
        }
        
        foreach ($results as $result) {
            $distribution[$result['note']] = $result['count'];
        }
        
        return $distribution;
    }

    /**
     * Récupère le nombre d'avis par mois pour les statistiques
     */
    public function getAvisByMonth(int $months = 12): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('SUBSTRING(a.dateAvis, 1, 7) as month, COUNT(a.id) as count')
            ->where('a.status = :status')
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults($months);
        
        return $qb->getQuery()->getResult();
    }

    // ========== MÉTHODES POUR LA GESTION DES STATUTS ==========

    /**
     * Récupère les avis par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les avis en attente
     */
    public function findPending(): array
    {
        return $this->findByStatus(Avis::STATUS_PENDING);
    }

    /**
     * Récupère les avis approuvés
     */
    public function findApproved(): array
    {
        return $this->findByStatus(Avis::STATUS_APPROVED);
    }

    /**
     * Récupère les avis rejetés
     */
    public function findRejected(): array
    {
        return $this->findByStatus(Avis::STATUS_REJECTED);
    }

    /**
     * Compte le nombre d'avis par statut
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->groupBy('a.status');
        
        $results = $qb->getQuery()->getResult();
        
        $counts = [
            Avis::STATUS_PENDING => 0,
            Avis::STATUS_APPROVED => 0,
            Avis::STATUS_REJECTED => 0
        ];
        
        foreach ($results as $result) {
            $counts[$result['status']] = $result['count'];
        }
        
        return $counts;
    }

    /**
     * Compte le nombre d'avis en attente
     */
    public function countPending(): int
    {
        return $this->count(['status' => Avis::STATUS_PENDING]);
    }

    /**
     * Compte le nombre d'avis approuvés
     */
    public function countApproved(): int
    {
        return $this->count(['status' => Avis::STATUS_APPROVED]);
    }

    /**
     * Compte le nombre d'avis rejetés
     */
    public function countRejected(): int
    {
        return $this->count(['status' => Avis::STATUS_REJECTED]);
    }

    // ========== MÉTHODES POUR LES DERNIERS AVIS ==========

    /**
     * Récupère les derniers avis (tous statuts confondus)
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.dateAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les derniers avis approuvés
     */
    public function findLatestApproved(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->orderBy('a.dateAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les derniers avis en attente
     */
    public function findLatestPending(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', Avis::STATUS_PENDING)
            ->orderBy('a.dateAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // ========== MÉTHODES DE RECHERCHE ==========

    /**
     * Recherche des avis par mot-clé dans le commentaire ou le nom
     */
    public function search(string $keyword): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.commentaire LIKE :keyword')
            ->orWhere('a.nomClient LIKE :keyword')
            ->orWhere('a.destination LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des avis par note
     */
    public function findByNote(int $note): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.note = :note')
            ->andWhere('a.status = :status')
            ->setParameter('note', $note)
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des avis par destination
     */
    public function findByDestination(string $destination): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.destination LIKE :destination')
            ->andWhere('a.status = :status')
            ->setParameter('destination', '%' . $destination . '%')
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ========== MÉTHODES POUR LES ACTIONS GROUPÉES ==========

    /**
     * Approuve plusieurs avis en une fois
     */
    public function bulkApprove(array $ids): int
    {
        $qb = $this->createQueryBuilder('a')
            ->update()
            ->set('a.status', ':status')
            ->where('a.id IN (:ids)')
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->setParameter('ids', $ids);
        
        return $qb->getQuery()->execute();
    }

    /**
     * Rejette plusieurs avis en une fois
     */
    public function bulkReject(array $ids): int
    {
        $qb = $this->createQueryBuilder('a')
            ->update()
            ->set('a.status', ':status')
            ->where('a.id IN (:ids)')
            ->setParameter('status', Avis::STATUS_REJECTED)
            ->setParameter('ids', $ids);
        
        return $qb->getQuery()->execute();
    }

    /**
     * Supprime plusieurs avis en une fois
     */
    public function bulkDelete(array $ids): int
    {
        $qb = $this->createQueryBuilder('a')
            ->delete()
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids);
        
        return $qb->getQuery()->execute();
    }

    // ========== MÉTHODES POUR L'API ==========

    /**
     * Récupère les avis avec leurs relations
     */
    public function findWithDetails(int $id): ?Avis
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.votes', 'v')
            ->addSelect('v')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les avis avec le plus de votes
     */
    public function findMostVoted(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('a, COUNT(v.id) as voteCount')
            ->leftJoin('a.votes', 'v')
            ->where('a.status = :status')
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->groupBy('a.id')
            ->orderBy('voteCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les avis avec analyse de sentiment positive
     */
    public function findPositiveSentiment(float $threshold = 0.7): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.sentimentScore >= :threshold')
            ->andWhere('a.status = :status')
            ->setParameter('threshold', $threshold)
            ->setParameter('status', Avis::STATUS_APPROVED)
            ->orderBy('a.sentimentScore', 'DESC')
            ->getQuery()
            ->getResult();
    }
}