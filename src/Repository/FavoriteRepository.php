<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Favorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneForUserAndEvent(int $userId, Evenement $evenement): ?Favorite
    {
        return $this->findOneBy([
            'userId' => $userId,
            'evenement' => $evenement,
        ]);
    }

    /**
     * @return int[]
     */
    public function findEventIdsByUserId(int $userId): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.evenement) AS eventId')
            ->andWhere('f.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['eventId'], $rows);
    }
}
