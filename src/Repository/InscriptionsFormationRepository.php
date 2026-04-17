<?php

namespace App\Repository;

use App\Entity\InscriptionsFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InscriptionsFormation>
 */
class InscriptionsFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InscriptionsFormation::class);
    }

    /** All inscriptions ordered by date desc, with utilisateur and formation eager-loaded */
    public function findAllWithDetails(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.utilisateur', 'u')
            ->leftJoin('i.formation', 'f')
            ->addSelect('u', 'f')
            ->orderBy('i.date_inscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
