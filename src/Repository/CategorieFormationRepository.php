<?php

namespace App\Repository;

use App\Entity\CategorieFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategorieFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieFormation::class);
    }

    public function search(string $q, string $sort = 'nom', string $dir = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.formations', 'f')
            ->where('c.nomCategorie LIKE :q OR c.description LIKE :q')
            ->setParameter('q', '%' . $q . '%');
        
        if ($sort === 'formations') {
            $qb->addSelect('COUNT(f.idFormation) as HIDDEN formationCount')
               ->groupBy('c.idCategorie')
               ->orderBy('formationCount', $dir === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('c.nomCategorie', $dir === 'DESC' ? 'DESC' : 'ASC');
        }
        
        return $qb->getQuery()->getResult();
    }

    public function findAllSorted(string $sort = 'nom', string $dir = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.formations', 'f');
        
        if ($sort === 'formations') {
            $qb->addSelect('COUNT(f.idFormation) as HIDDEN formationCount')
               ->groupBy('c.idCategorie')
               ->orderBy('formationCount', $dir === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('c.nomCategorie', $dir === 'DESC' ? 'DESC' : 'ASC');
        }
        
        return $qb->getQuery()->getResult();
    }
}
