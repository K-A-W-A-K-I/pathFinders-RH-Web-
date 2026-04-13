<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function search(string $q, ?int $categorieId = null, string $sort = 'titre', string $dir = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('f')
            ->join('f.categorie', 'c')
            ->where('f.titre LIKE :q OR f.description LIKE :q OR f.formateur LIKE :q')
            ->setParameter('q', '%' . $q . '%');

        if ($categorieId) {
            $qb->andWhere('c.idCategorie = :cat')->setParameter('cat', $categorieId);
        }

        $allowed = ['titre', 'dureeHeures', 'placeDisponible'];
        $field = in_array($sort, $allowed) ? 'f.' . $sort : 'f.titre';
        $direction = $dir === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($field, $direction);

        return $qb->getQuery()->getResult();
    }

    public function findByCategorieAndSort(?int $categorieId, string $sort = 'titre', string $dir = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('f')->join('f.categorie', 'c');

        if ($categorieId) {
            $qb->where('c.idCategorie = :cat')->setParameter('cat', $categorieId);
        }

        $allowed = ['titre', 'dureeHeures', 'placeDisponible'];
        $field = in_array($sort, $allowed) ? 'f.' . $sort : 'f.titre';
        $direction = $dir === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($field, $direction);

        return $qb->getQuery()->getResult();
    }
}
