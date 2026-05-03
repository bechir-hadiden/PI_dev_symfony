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

    // ─────────────────────────────────────────────────────────────────────────
    // ❌ AVANT optimisation — Problème N+1
    // findAll() ne charge PAS les voyages → chaque appel à getVoyages()
    // dans la vue Twig déclenche 1 requête SQL supplémentaire !
    //
    // Exemple avec 10 destinations :
    //   1 requête findAll()
    // + 10 requêtes getVoyages() = 11 requêtes SQL au total  ← N+1 !
    // ─────────────────────────────────────────────────────────────────────────

    // ✅ DÉJÀ OPTIMISÉ — findWithVoyages() avec LEFT JOIN
    // 1 seule requête SQL pour tout charger d'un coup
    public function findWithVoyages(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.voyages', 'v')
            ->addSelect('v') // ← charge les voyages en 1 requête
            ->orderBy('d.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ DÉJÀ OPTIMISÉ — search() avec LIKE sur nom ET pays
    public function search(string $q): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.nom LIKE :q OR d.pays LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ✅ NOUVELLES MÉTHODES OPTIMISÉES (ajoutées pour le rapport)
    // ─────────────────────────────────────────────────────────────────────────

    // ✅ OPTIMISATION 1 — Pagination pour éviter de charger toutes les données
    // ❌ AVANT : findAll() charge TOUT en mémoire
    // ✅ APRÈS : setMaxResults() limite les données chargées
    public function findPaginated(int $page = 1, int $limit = 9): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.voyages', 'v')
            ->addSelect('v')
            ->orderBy('d.order', 'ASC')
            ->setFirstResult(($page - 1) * $limit) // offset
            ->setMaxResults($limit) // limit
            ->getQuery()
            ->getResult();
    }

    // ✅ OPTIMISATION 2 — Recherche optimisée avec voyages préchargés
    // ❌ AVANT : search() ne charge pas les voyages → N+1 dans la vue
    // ✅ APRÈS : JOIN inclus pour éviter les requêtes supplémentaires
    public function searchAvecVoyages(string $q): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.voyages', 'v')
            ->addSelect('v')
            ->andWhere('d.nom LIKE :q OR d.pays LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ OPTIMISATION 3 — Compter sans charger les entités
    // ❌ AVANT : count($this->findAll()) → charge tout en mémoire juste pour compter
    // ✅ APRÈS : COUNT() en SQL → 1 requête légère, pas de chargement d'entités
    public function countAll(): int
    {
        return (int)$this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // ✅ OPTIMISATION 4 — Requête filtrée par pays
    // Évite de charger toutes les destinations pour filtrer en PHP
    public function findByPays(string $pays): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.voyages', 'v')
            ->addSelect('v')
            ->andWhere('d.pays = :pays')
            ->setParameter('pays', $pays)
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForHome(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.voyages', 'v')
            ->addSelect('v')
            ->orderBy('d.id', 'ASC')
            ->setMaxResults($limit);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb, true);
        return iterator_to_array($paginator);
    }
}