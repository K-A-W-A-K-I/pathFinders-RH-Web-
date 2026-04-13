<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Search & filter events with pagination support.
     *
     * @return Evenement[]
     */
    public function findByFilters(
        ?string $search = null,
        ?string $statut = null,
        ?int    $categorieId = null,
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')
            ->addSelect('c')
            ->orderBy('e.dateCreation', 'DESC');

        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('e.titre', ':search'),
                    $qb->expr()->like('e.lieu', ':search'),
                    $qb->expr()->like('e.description', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            $qb->andWhere('e.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($categorieId) {
            $qb->andWhere('IDENTITY(e.categorie) = :catId')
               ->setParameter('catId', $categorieId);
        }

        if ($type) {
            $qb->andWhere('e.typeEvenement = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count events grouped by statut for dashboard stats.
     */
    public function countByStatut(): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.statut, COUNT(e.id) as total')
            ->groupBy('e.statut')
            ->getQuery()
            ->getResult();

        $counts = ['Actif' => 0, 'Complet' => 0, 'Annulé' => 0, 'Terminé' => 0];
        foreach ($results as $r) {
            $counts[$r['statut']] = (int) $r['total'];
        }
        return $counts;
    }

    /**
     * Get upcoming events (date_debut >= today, statut = Actif).
     */
    public function findUpcoming(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')
            ->addSelect('c')
            ->where('e.dateDebut >= :today')
            ->andWhere('e.statut = :statut')
            ->setParameter('today', new \DateTime())
            ->setParameter('statut', 'Actif')
            ->orderBy('e.dateDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
