<?php

namespace App\Repository;

use App\Entity\Candidature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.offre', 'o')
            ->join('c.candidat', 'ca')
            ->orderBy('c.datePassage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCandidat(int $idCandidat): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.offre', 'o')
            ->where('c.candidat = :id')
            ->setParameter('id', $idCandidat)
            ->orderBy('c.datePassage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOffre(int $idOffre): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.candidat', 'ca')
            ->where('c.offre = :id')
            ->setParameter('id', $idOffre)
            ->orderBy('c.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function dejaPostule(int $idCandidat, int $idOffre): bool
    {
        return (bool) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.candidat = :candidat AND c.offre = :offre')
            ->setParameter('candidat', $idCandidat)
            ->setParameter('offre', $idOffre)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
