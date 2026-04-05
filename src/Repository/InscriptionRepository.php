<?php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    public function isAlreadyInscrit(string $sessionId, int $formationId): bool
    {
        return (bool) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.sessionId = :sid AND i.formation = :fid')
            ->setParameter('sid', $sessionId)
            ->setParameter('fid', $formationId)
            ->getQuery()->getSingleScalarResult();
    }

    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.formation = :fid')
            ->setParameter('fid', $formationId)
            ->orderBy('i.dateInscription', 'DESC')
            ->getQuery()->getResult();
    }
}
