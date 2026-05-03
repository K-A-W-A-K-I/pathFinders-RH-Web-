<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Trouve toutes les questions pour une offre donnée.
     * OPTIMISATION N+1: Utilise addSelect() et JOIN pour charger l'offre en une seule requête.
     * 
     * @param int $idOffre
     * @return Question[]
     */
    public function findByOffre(int $idOffre): array
    {
        return $this->createQueryBuilder('q')
            ->addSelect('o')  // Optimisation: Charge l'offre avec les questions
            ->leftJoin('q.offre', 'o')  // JOIN pour éviter N+1
            ->where('q.offre = :id')
            ->setParameter('id', $idOffre)
            ->getQuery()
            ->getResult();
    }
}
