<?php

namespace App\Service;

use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionsFormationRepository;
use Doctrine\DBAL\Connection;

class FormationStatsService
{
    public function __construct(
        private FormationRepository $formationRepo,
        private InscriptionsFormationRepository $inscFormationRepo,
        private CategorieFormationRepository $categorieRepo,
        private Connection $connection,
    ) {}

    /** Nombre d'inscriptions par formation (table inscriptions_formation) */
    public function getNbInscriptionsParFormation(): array
    {
        $qb = $this->inscFormationRepo->createQueryBuilder('i')
            ->select('f.idFormation as id, f.titre as titre, COUNT(i.id_inscription) as nb')
            ->join('i.formation', 'f')
            ->groupBy('f.idFormation')
            ->orderBy('nb', 'DESC');

        $rows = $qb->getQuery()->getResult();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = ['titre' => $row['titre'], 'nb' => (int) $row['nb']];
        }
        return $result;
    }

    /** Taux de remplissage par formation en % */
    public function getTauxRemplissageParFormation(): array
    {
        $formations = $this->formationRepo->findAll();
        $result = [];
        foreach ($formations as $f) {
            $cap   = $f->getCapaciteMax();
            $dispo = $f->getPlaceDisponible();
            $taux  = $cap > 0 ? round(($cap - $dispo) / $cap * 100, 1) : 0;
            $result[$f->getIdFormation()] = [
                'titre' => $f->getTitre(),
                'taux'  => $taux,
                'cap'   => $cap,
                'dispo' => $dispo,
            ];
        }
        return $result;
    }

    /** Top N formations par nombre d'inscrits (table inscriptions_formation) */
    public function getTopFormations(int $limit = 5): array
    {
        $qb = $this->inscFormationRepo->createQueryBuilder('i')
            ->select('f.idFormation as id, f.titre as titre, COUNT(i.id_inscription) as nb')
            ->join('i.formation', 'f')
            ->groupBy('f.idFormation')
            ->orderBy('nb', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /** Stats globales */
    public function getStatsGlobales(): array
    {
        $formations     = $this->formationRepo->findAll();
        $nbFormations   = count($formations);
        $nbInscriptions = count($this->inscFormationRepo->findAll());

        $completes     = 0;
        $totalTaux     = 0;
        $plusPopulaire = null;
        $maxInscrits   = -1;

        $nbParFormation = $this->getNbInscriptionsParFormation();

        foreach ($formations as $f) {
            $cap   = $f->getCapaciteMax();
            $dispo = $f->getPlaceDisponible();
            if ($cap > 0) {
                $taux = ($cap - $dispo) / $cap * 100;
                $totalTaux += $taux;
                if ($dispo === 0) $completes++;
            }
            $nb = $nbParFormation[$f->getIdFormation()]['nb'] ?? 0;
            if ($nb > $maxInscrits) {
                $maxInscrits   = $nb;
                $plusPopulaire = $f->getTitre();
            }
        }

        $tauxMoyen = $nbFormations > 0 ? round($totalTaux / $nbFormations, 1) : 0;

        return [
            'nbFormations'   => $nbFormations,
            'nbInscriptions' => $nbInscriptions,
            'nbCompletes'    => $completes,
            'tauxMoyen'      => $tauxMoyen,
            'plusPopulaire'  => $plusPopulaire,
        ];
    }

    /** Inscriptions par catégorie (toutes catégories, même vides) */
    public function getStatsParCategorie(): array
    {
        $categories     = $this->categorieRepo->findAll();
        $nbParFormation = $this->getNbInscriptionsParFormation();

        $result = [];
        foreach ($categories as $cat) {
            $total = 0;
            foreach ($cat->getFormations() as $f) {
                $total += $nbParFormation[$f->getIdFormation()]['nb'] ?? 0;
            }
            $result[] = [
                'categorie' => $cat->getNomCategorie(),
                'nb'        => $total,
            ];
        }
        return $result;
    }

    /** Progression moyenne par formation (table inscriptions_formation) */
    public function getProgressionMoyenneParFormation(): array
    {
        $qb = $this->inscFormationRepo->createQueryBuilder('i')
            ->select('f.idFormation as id, f.titre as titre, AVG(i.pourcentage_progression) as avg_prog')
            ->join('i.formation', 'f')
            ->where('i.pourcentage_progression IS NOT NULL')
            ->groupBy('f.idFormation')
            ->orderBy('avg_prog', 'DESC');

        $rows   = $qb->getQuery()->getResult();
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'titre'    => $row['titre'],
                'avg_prog' => round((float) $row['avg_prog'], 1),
            ];
        }
        return $result;
    }

    /** Tendance des inscriptions sur les 6 derniers mois (table inscriptions_formation) */
    public function getTendanceInscriptionsParMois(): array
    {
        $sql = "
            SELECT DATE_FORMAT(date_inscription, '%Y-%m') as mois,
                   COUNT(*) as nb
            FROM inscriptions_formation
            WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY mois
            ORDER BY mois ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql);

        // Remplir les mois manquants avec 0
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $key          = (new \DateTime("-$i months"))->format('Y-m');
            $result[$key] = 0;
        }
        foreach ($rows as $row) {
            $result[$row['mois']] = (int) $row['nb'];
        }

        $labels  = [];
        $values  = [];
        $moisFr  = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
        foreach ($result as $key => $nb) {
            [$y, $m]  = explode('-', $key);
            $labels[] = $moisFr[(int)$m - 1] . ' ' . $y;
            $values[] = $nb;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** Alertes contextuelles automatiques */
    public function getInterpretationsContextuelles(): array
    {
        $alerts         = [];
        $tauxRemplissage = $this->getTauxRemplissageParFormation();
        $nbParFormation  = $this->getNbInscriptionsParFormation();

        foreach ($tauxRemplissage as $id => $data) {
            if ($data['taux'] >= 100) {
                $alerts[] = ['type' => 'danger',  'msg' => "🔴 « {$data['titre']} » est complète (100% remplie)."];
            } elseif ($data['taux'] >= 80) {
                $alerts[] = ['type' => 'warning', 'msg' => "🟡 « {$data['titre']} » est presque pleine ({$data['taux']}%)."];
            } elseif ($data['taux'] === 0.0 && ($nbParFormation[$id]['nb'] ?? 0) === 0) {
                $alerts[] = ['type' => 'info',    'msg' => "🔵 « {$data['titre']} » n'a aucune inscription."];
            }
        }

        if (empty($alerts)) {
            $alerts[] = ['type' => 'success', 'msg' => '✅ Toutes les formations sont dans un état normal.'];
        }

        return $alerts;
    }
}
