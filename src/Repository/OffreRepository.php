<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.statut = :statut')
            ->setParameter('statut', 'active')
            ->orderBy('o.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFiltered(?string $search, ?string $domaine, ?string $contrat, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.statut = :statut')
            ->setParameter('statut', 'active');

        if ($search) {
            $qb->andWhere('o.titre LIKE :search OR o.description LIKE :search OR o.domaine LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($domaine && $domaine !== 'Tous') {
            $qb->andWhere('o.domaine = :domaine')->setParameter('domaine', $domaine);
        }
        if ($contrat && $contrat !== 'Tous') {
            $qb->andWhere('o.typeContrat = :contrat')->setParameter('contrat', $contrat);
        }

        match($sort) {
            'titre'      => $qb->orderBy('o.titre', 'ASC'),
            'domaine'    => $qb->orderBy('o.domaine', 'ASC'),
            'salaire'    => $qb->orderBy('o.salaireMin', 'ASC'),
            default      => $qb->orderBy('o.datePublication', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }
}
