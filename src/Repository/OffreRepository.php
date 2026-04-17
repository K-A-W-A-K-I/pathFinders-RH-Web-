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

    /**
     * Retourne les offres recommandées pour un candidat.
     * Logique : offres actives non encore postulées, triées par score de pertinence :
     *   - +3 pts si même domaine que les candidatures passées
     *   - +2 pts si même type de contrat
     *   - +1 pt par tranche de 20% de score CV IA moyen du candidat
     *
     * @param array $domainesPostules   domaines des candidatures passées
     * @param array $contratsPostules   types de contrat des candidatures passées
     * @param array $offresDejaPostulees ids des offres déjà postulées
     * @param int   $cvScoreMoyen       score CV IA moyen du candidat (0-100)
     * @param int   $limit              nombre max de recommandations
     */
    public function findRecommended(
        array $domainesPostules,
        array $contratsPostules,
        array $offresDejaPostulees,
        int $cvScoreMoyen = 0,
        int $limit = 4
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->where('o.statut = :statut')
            ->setParameter('statut', 'active');

        if (!empty($offresDejaPostulees)) {
            $qb->andWhere('o.id NOT IN (:deja)')
               ->setParameter('deja', $offresDejaPostulees);
        }

        $offres = $qb->getQuery()->getResult();

        // Calculer un score de pertinence pour chaque offre
        $scored = [];
        foreach ($offres as $offre) {
            $score = 0;
            if (in_array($offre->getDomaine(), $domainesPostules, true)) {
                $score += 3;
            }
            if (in_array($offre->getTypeContrat(), $contratsPostules, true)) {
                $score += 2;
            }
            // Bonus si le score CV moyen est suffisant pour cette offre
            if ($cvScoreMoyen >= $offre->getScoreMinimum()) {
                $score += 1 + (int) floor($cvScoreMoyen / 20);
            }
            if ($score > 0) {
                $scored[] = ['offre' => $offre, 'score' => $score];
            }
        }

        // Trier par score décroissant
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'offre'), 0, $limit);
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
