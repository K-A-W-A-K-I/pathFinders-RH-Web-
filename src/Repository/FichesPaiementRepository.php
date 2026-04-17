<?php

namespace App\Repository;

use App\Entity\FichesPaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FichesPaiement>
 */
class FichesPaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FichesPaiement::class);
    }
   public function findCurrentMonthByEmployee(int $employeeId): array
{
    $now = new \DateTime();
    $firstDay = new \DateTime('first day of this month');
    $firstDay->setTime(0, 0, 0);
    $lastDay = new \DateTime('last day of this month');
    $lastDay->setTime(23, 59, 59);

    return $this->createQueryBuilder('f')
        ->where('f.employee = :emp')
        ->andWhere('f.date_paiement >= :firstDay')
        ->andWhere('f.date_paiement <= :lastDay')
        ->setParameter('emp', $employeeId)
        ->setParameter('firstDay', $firstDay)
        ->setParameter('lastDay', $lastDay)
        ->getQuery()
        ->getResult();
}

public function findAllByEmployee(int $employeeId): array
{
    return $this->createQueryBuilder('f')
        ->where('f.employee = :emp')
        ->setParameter('emp', $employeeId)
        ->orderBy('f.date_paiement', 'DESC')
        ->getQuery()
        ->getResult();
}

    //    /**
    //     * @return FichesPaiement[] Returns an array of FichesPaiement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FichesPaiement
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
