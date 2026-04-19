<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.idUtilisateur = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('r.dateCreation', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * @return Reclamation[]
     */
    public function findRecentByUser(int $userId, int $days = 30, int $limit = 20): array
    {
        $since = (new \DateTimeImmutable())->modify(sprintf('-%d days', max(1, $days)));

        return $this->createQueryBuilder('r')
            ->where('r.idUtilisateur = :uid')
            ->andWhere('r.dateCreation >= :since')
            ->setParameter('uid', $userId)
            ->setParameter('since', $since)
            ->orderBy('r.dateCreation', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,int>
     */
    public function findOpenAgeHoursByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id_reclamation, TIMESTAMPDIFF(HOUR, date_creation, NOW()) AS age_hours
                FROM reclamation
                WHERE id_reclamation IN ($placeholders)";

        $rows = $conn->fetchAllAssociative($sql, array_values($ids));
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id_reclamation']] = max(0, (int) $row['age_hours']);
        }

        return $result;
    }

    /**
     * @param array<int,int> $userIds
     * @return array<int,int>
     */
    public function countRecentByUserIds(array $userIds, int $hours = 24): array
    {
        if ($userIds === []) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT id_utilisateur, COUNT(*) AS total
                FROM reclamation
                WHERE id_utilisateur IN ($placeholders)
                  AND date_creation >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY id_utilisateur";

        $params = array_merge(array_values($userIds), [max(1, $hours)]);
        $rows = $conn->fetchAllAssociative($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id_utilisateur']] = (int) $row['total'];
        }

        return $result;
    }

    public function countRecentByExactTitle(string $title, int $hours = 48, ?int $excludeId = null): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('LOWER(r.titre) = :title')
            ->andWhere('r.dateCreation >= :since')
            ->setParameter('title', mb_strtolower(trim($title)))
            ->setParameter('since', (new \DateTimeImmutable())->modify(sprintf('-%d hours', max(1, $hours))));

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int,int> $userIds
     * @return array<int,array{total:int,open:int,rejected:int,review:int,resolved:int,repondu:int}>
     */
    public function findUserReclamationStats(array $userIds, int $days = 90): array
    {
        if ($userIds === []) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT
                    id_utilisateur,
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut IN ('En attente', 'En cours') THEN 1 ELSE 0 END) AS open_count,
                    SUM(CASE WHEN moderation_decision = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                    SUM(CASE WHEN moderation_decision = 'review' THEN 1 ELSE 0 END) AS review_count,
                    SUM(CASE WHEN statut = 'Résolu' THEN 1 ELSE 0 END) AS resolved_count,
                    SUM(CASE WHEN statut = 'Répondu' THEN 1 ELSE 0 END) AS repondu_count
                FROM reclamation
                WHERE id_utilisateur IN ($placeholders)
                  AND date_creation >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY id_utilisateur";

        $params = array_merge(array_values($userIds), [max(1, $days)]);
        $rows = $conn->fetchAllAssociative($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $uid = (int) $row['id_utilisateur'];
            $result[$uid] = [
                'total' => (int) $row['total'],
                'open' => (int) $row['open_count'],
                'rejected' => (int) $row['rejected_count'],
                'review' => (int) $row['review_count'],
                'resolved' => (int) $row['resolved_count'],
                'repondu' => (int) $row['repondu_count'],
            ];
        }

        return $result;
    }
}
