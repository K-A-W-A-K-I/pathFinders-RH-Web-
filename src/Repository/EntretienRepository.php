<?php

namespace App\Repository;

use App\Entity\Entretien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntretienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entretien::class);
    }

    public function findAllWithRelations(?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.candidat', 'ca')
            ->join('e.offre', 'o')
            ->orderBy('e.dateEntretien', 'ASC');

        if ($statut && $statut !== 'Tous') {
            $map = ['En attente' => 'EN_ATTENTE', 'Confirmés' => 'CONFIRME', 'Refusés' => 'REFUSE'];
            $qb->where('e.statut = :statut')->setParameter('statut', $map[$statut] ?? $statut);
        }

        return $qb->getQuery()->getResult();
    }

    public function getBookedSlots(): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.dateEntretien')
            ->where('e.statut != :refuse')
            ->setParameter('refuse', 'REFUSE')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(function ($d) {
            return $d instanceof \DateTimeInterface ? $d : new \DateTime((string) $d);
        }, $results);
    }

    public function hasConflict(\DateTimeInterface $date, int $excludeId = -1): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM entretiens 
                WHERE statut != 'REFUSE' 
                AND ABS(TIMESTAMPDIFF(MINUTE, date_entretien, :date)) < 30 
                AND id_entretien != :exclude";
        return (bool) $conn->fetchOne($sql, ['date' => $date->format('Y-m-d H:i:s'), 'exclude' => $excludeId]);
    }

    public function findByCandidat(int $idCandidat): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.candidat = :id')
            ->setParameter('id', $idCandidat)
            ->orderBy('e.dateEntretien', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
