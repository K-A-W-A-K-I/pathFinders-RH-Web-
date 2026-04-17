<?php

namespace App\Service;

use App\Repository\FormationRepository;
use App\Repository\InscriptionsFormationRepository;
use App\Repository\CategorieFormationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier avancé pour les statistiques des formations.
 * Calcule via Doctrine : inscriptions, taux de remplissage,
 * formations populaires, statistiques par catégorie.
 */
class FormationStatsService
{
    public function __construct(
        private EntityManagerInterface          $em,
        private FormationRepository             $formationRepo,
        private InscriptionsFormationRepository $inscFormRepo,
        private CategorieFormationRepository    $categorieRepo
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // 1. Nombre d'inscriptions par formation (toutes tables confondues)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne le nombre d'inscriptions par formation.
     * Basé uniquement sur inscriptions_formation (utilisateurs connectés).
     *
     * @return array<int, int>  [idFormation => nbInscrits]
     */
    public function getNbInscriptionsParFormation(): array
    {
        $rows = $this->inscFormRepo->createQueryBuilder('i')
            ->select('IDENTITY(i.formation) AS fid, COUNT(i.id_inscription) AS nb')
            ->groupBy('i.formation')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['fid']] = (int)$row['nb'];
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Taux de remplissage par formation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne le taux de remplissage (%) par formation.
     * taux = (capaciteMax - placeDisponible) / capaciteMax * 100
     *
     * @return array<int, float>  [idFormation => tauxRemplissage]
     */
    public function getTauxRemplissageParFormation(): array
    {
        $formations = $this->formationRepo->createQueryBuilder('f')
            ->select('f.idFormation, f.titre, f.capaciteMax, f.placeDisponible')
            ->where('f.capaciteMax > 0')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($formations as $f) {
            $inscrites = $f['capaciteMax'] - $f['placeDisponible'];
            $result[$f['idFormation']] = round(($inscrites / $f['capaciteMax']) * 100, 1);
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Formations les plus populaires (globales)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne le top N formations par nombre d'inscriptions.
     *
     * @return array<int, array{idFormation: int, titre: string, nbInscrits: int, categorie: string}>
     */
    public function getTopFormations(int $limit = 5): array
    {
        $nbParFormation = $this->getNbInscriptionsParFormation();
        arsort($nbParFormation);
        $topIds = array_slice(array_keys($nbParFormation), 0, $limit, true);

        if (empty($topIds)) return [];

        $formations = $this->formationRepo->createQueryBuilder('f')
            ->join('f.categorie', 'c')
            ->select('f.idFormation, f.titre, c.nomCategorie')
            ->where('f.idFormation IN (:ids)')
            ->setParameter('ids', $topIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($formations as $f) {
            $result[] = [
                'idFormation' => $f['idFormation'],
                'titre'       => $f['titre'],
                'nbInscrits'  => $nbParFormation[$f['idFormation']] ?? 0,
                'categorie'   => $f['nomCategorie'],
            ];
        }

        usort($result, fn($a, $b) => $b['nbInscrits'] - $a['nbInscrits']);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. Formations populaires par catégorie
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne la formation la plus populaire pour chaque catégorie.
     *
     * @return array<string, array{titre: string, nbInscrits: int}>
     *         [nomCategorie => {titre, nbInscrits}]
     */
    public function getTopFormationParCategorie(): array
    {
        $nbParFormation = $this->getNbInscriptionsParFormation();

        $formations = $this->formationRepo->createQueryBuilder('f')
            ->join('f.categorie', 'c')
            ->select('f.idFormation, f.titre, c.nomCategorie')
            ->getQuery()
            ->getResult();

        $parCategorie = [];
        foreach ($formations as $f) {
            $cat = $f['nomCategorie'];
            $nb  = $nbParFormation[$f['idFormation']] ?? 0;
            if (!isset($parCategorie[$cat]) || $nb > $parCategorie[$cat]['nbInscrits']) {
                $parCategorie[$cat] = [
                    'titre'      => $f['titre'],
                    'nbInscrits' => $nb,
                ];
            }
        }

        return $parCategorie;
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. Statistiques globales (tableau de bord)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne un résumé global des statistiques.
     *
     * @return array{
     *   totalFormations: int,
     *   totalInscriptions: int,
     *   formationsCompletes: int,
     *   tauxRemplissageMoyen: float,
     *   formationPlusPopulaire: string|null,
     * }
     */
    public function getStatsGlobales(): array
    {
        $formations = $this->formationRepo->findAll();
        $totalFormations = count($formations);

        $nbParFormation = $this->getNbInscriptionsParFormation();
        $totalInscriptions = array_sum($nbParFormation);

        $tauxParFormation = $this->getTauxRemplissageParFormation();
        $formationsCompletes = count(array_filter(
            $formations,
            fn($f) => $f->getPlaceDisponible() === 0
        ));
        $tauxMoyen = count($tauxParFormation) > 0
            ? round(array_sum($tauxParFormation) / count($tauxParFormation), 1)
            : 0;

        $topFormations = $this->getTopFormations(1);
        $plusPopulaire = !empty($topFormations) ? $topFormations[0]['titre'] : null;

        return [
            'totalFormations'        => $totalFormations,
            'totalInscriptions'      => $totalInscriptions,
            'formationsCompletes'    => $formationsCompletes,
            'tauxRemplissageMoyen'   => $tauxMoyen,
            'formationPlusPopulaire' => $plusPopulaire,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // 6. Statistiques par catégorie
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne les statistiques agrégées par catégorie.
     *
     * @return array<string, array{
     *   nbFormations: int,
     *   nbInscrits: int,
     *   tauxRemplissageMoyen: float,
     * }>
     */
    public function getStatsParCategorie(): array
    {
        // Partir de TOUTES les catégories (même sans formation)
        $categories = $this->categorieRepo->findAll();
        $nbParFormation = $this->getNbInscriptionsParFormation();

        // Construire le résultat initialisé à 0 pour chaque catégorie
        $result = [];
        foreach ($categories as $cat) {
            $result[$cat->getNomCategorie()] = [
                'nbFormations'        => 0,
                'nbInscrits'          => 0,
                'tauxTotal'           => 0,
                'tauxRemplissageMoyen'=> 0,
            ];
        }

        // Remplir avec les formations existantes
        $formations = $this->formationRepo->createQueryBuilder('f')
            ->join('f.categorie', 'c')
            ->select('f.idFormation, f.capaciteMax, f.placeDisponible, c.nomCategorie')
            ->getQuery()
            ->getResult();

        foreach ($formations as $f) {
            $cat = $f['nomCategorie'];
            if (!isset($result[$cat])) {
                $result[$cat] = ['nbFormations' => 0, 'nbInscrits' => 0, 'tauxTotal' => 0, 'tauxRemplissageMoyen' => 0];
            }
            $result[$cat]['nbFormations']++;
            $result[$cat]['nbInscrits'] += $nbParFormation[$f['idFormation']] ?? 0;
            if ($f['capaciteMax'] > 0) {
                $result[$cat]['tauxTotal'] += (($f['capaciteMax'] - $f['placeDisponible']) / $f['capaciteMax']) * 100;
            }
        }

        // Calculer le taux moyen
        foreach ($result as $cat => &$stats) {
            $stats['tauxRemplissageMoyen'] = $stats['nbFormations'] > 0
                ? round($stats['tauxTotal'] / $stats['nbFormations'], 1)
                : 0;
            unset($stats['tauxTotal']);
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // 7. Progression moyenne des utilisateurs par formation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne la progression moyenne (%) des inscrits pour chaque formation.
     *
     * @return array<int, float>  [idFormation => progressionMoyenne]
     */
    public function getProgressionMoyenneParFormation(): array
    {
        $rows = $this->inscFormRepo->createQueryBuilder('i')
            ->select('IDENTITY(i.formation) AS fid, AVG(i.pourcentage_progression) AS avgProg')
            ->groupBy('i.formation')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['fid']] = round((float)$row['avgProg'], 1);
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // 8. Corrélation de Pearson entre capaciteMax et nbInscrits
    // ─────────────────────────────────────────────────────────────────

    /**
     * Corrélation entre capaciteMax et nbInscrits.
     * Retourne le coefficient de corrélation de Pearson (-1 à 1).
     */
    public function getCorrelationCapaciteInscriptions(): float
    {
        $formations = $this->formationRepo->createQueryBuilder('f')
            ->select('f.idFormation, f.capaciteMax')
            ->where('f.capaciteMax > 0')
            ->getQuery()
            ->getResult();

        if (count($formations) < 2) {
            return 0.0;
        }

        $nbParFormation = $this->getNbInscriptionsParFormation();

        $xs = [];
        $ys = [];
        foreach ($formations as $f) {
            $xs[] = (float)$f['capaciteMax'];
            $ys[] = (float)($nbParFormation[$f['idFormation']] ?? 0);
        }

        $n    = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXY = 0.0;
        $sumX2 = 0.0;
        $sumY2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $ys[$i];
            $sumX2 += $xs[$i] ** 2;
            $sumY2 += $ys[$i] ** 2;
        }

        $numerator   = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX ** 2) * ($n * $sumY2 - $sumY ** 2));

        if ($denominator == 0.0) {
            return 0.0;
        }

        return round($numerator / $denominator, 4);
    }

    // ─────────────────────────────────────────────────────────────────
    // 9. Régression linéaire simple : nbInscrits = a * capaciteMax + b
    // ─────────────────────────────────────────────────────────────────

    /**
     * Régression linéaire simple : nbInscrits = a * capaciteMax + b.
     * Retourne ['a' => float, 'b' => float, 'r2' => float].
     *
     * @return array{a: float, b: float, r2: float}
     */
    public function getRegressionsLineaire(): array
    {
        $formations = $this->formationRepo->createQueryBuilder('f')
            ->select('f.idFormation, f.capaciteMax')
            ->where('f.capaciteMax > 0')
            ->getQuery()
            ->getResult();

        if (count($formations) < 2) {
            return ['a' => 0.0, 'b' => 0.0, 'r2' => 0.0];
        }

        $nbParFormation = $this->getNbInscriptionsParFormation();

        $xs = [];
        $ys = [];
        foreach ($formations as $f) {
            $xs[] = (float)$f['capaciteMax'];
            $ys[] = (float)($nbParFormation[$f['idFormation']] ?? 0);
        }

        $n    = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXY = 0.0;
        $sumX2 = 0.0;
        $sumY2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $ys[$i];
            $sumX2 += $xs[$i] ** 2;
            $sumY2 += $ys[$i] ** 2;
        }

        $denominator = $n * $sumX2 - $sumX ** 2;
        if ($denominator == 0.0) {
            return ['a' => 0.0, 'b' => 0.0, 'r2' => 0.0];
        }

        $a = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $b = ($sumY - $a * $sumX) / $n;

        // r² = r * r
        $corrDenom = sqrt($denominator * ($n * $sumY2 - $sumY ** 2));
        $r = $corrDenom != 0.0 ? ($n * $sumXY - $sumX * $sumY) / $corrDenom : 0.0;
        $r2 = round($r * $r, 4);

        return [
            'a'  => round($a, 4),
            'b'  => round($b, 4),
            'r2' => $r2,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // 10. Segmentation des formations en 3 groupes
    // ─────────────────────────────────────────────────────────────────

    /**
     * Segmentation des formations en 3 groupes basés sur nbInscrits :
     * - "Populaire" : nbInscrits >= 66e percentile
     * - "Moyenne"   : nbInscrits >= 33e percentile
     * - "Faible"    : nbInscrits < 33e percentile
     *
     * @return array{Populaire: array, Moyenne: array, Faible: array}
     */
    public function getSegmentationFormations(): array
    {
        $formations = $this->formationRepo->createQueryBuilder('f')
            ->select('f.idFormation, f.titre, f.capaciteMax')
            ->getQuery()
            ->getResult();

        $nbParFormation = $this->getNbInscriptionsParFormation();

        // Attach nbInscrits to each formation
        $data = [];
        foreach ($formations as $f) {
            $data[] = [
                'idFormation' => $f['idFormation'],
                'titre'       => $f['titre'],
                'capaciteMax' => $f['capaciteMax'],
                'nbInscrits'  => $nbParFormation[$f['idFormation']] ?? 0,
            ];
        }

        // Sort by nbInscrits ascending to compute percentiles
        usort($data, fn($a, $b) => $a['nbInscrits'] <=> $b['nbInscrits']);

        $n = count($data);
        if ($n === 0) {
            return ['Populaire' => [], 'Moyenne' => [], 'Faible' => []];
        }

        // Compute 33rd and 66th percentile values
        $idx33 = (int)floor($n * 0.33);
        $idx66 = (int)floor($n * 0.66);

        $p33 = $data[$idx33]['nbInscrits'];
        $p66 = $data[$idx66]['nbInscrits'];

        $result = ['Populaire' => [], 'Moyenne' => [], 'Faible' => []];

        foreach ($data as $f) {
            if ($f['nbInscrits'] >= $p66) {
                $result['Populaire'][] = $f;
            } elseif ($f['nbInscrits'] >= $p33) {
                $result['Moyenne'][] = $f;
            } else {
                $result['Faible'][] = $f;
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // 11. Interprétations contextuelles automatiques
    // ─────────────────────────────────────────────────────────────────

    /**
     * Analyse les données et retourne des insights textuels automatiques.
     *
     * @return string[]
     */
    public function getInterpretationsContextuelles(): array
    {
        $insights = [];

        $statsGlobales   = $this->getStatsGlobales();
        $segmentation    = $this->getSegmentationFormations();
        $correlation     = $this->getCorrelationCapaciteInscriptions();
        $nbParFormation  = $this->getNbInscriptionsParFormation();

        $tauxMoyen         = $statsGlobales['tauxRemplissageMoyen'];
        $totalFormations   = $statsGlobales['totalFormations'];
        $formationsCompletes = $statsGlobales['formationsCompletes'];

        // Insight 1 — Forte tension remplissage
        if ($tauxMoyen > 80) {
            $insights[] = sprintf(
                '⚠️ Forte tension : %s%% de taux de remplissage moyen. Envisagez d\'augmenter les capacités.',
                $tauxMoyen
            );
        }

        // Insight 2 — Plus de la moitié des formations complètes
        if ($totalFormations > 0 && $formationsCompletes > ($totalFormations / 2)) {
            $insights[] = '🔴 Plus de la moitié des formations sont complètes, la demande dépasse l\'offre.';
        }

        // Insight 3 — Corrélation forte ou faible
        if ($correlation > 0.7) {
            $insights[] = sprintf(
                '📈 Forte corrélation positive entre capacité et inscriptions (r=%s) : les formations avec plus de places attirent plus d\'inscrits.',
                $correlation
            );
        } elseif (abs($correlation) < 0.3) {
            $insights[] = '📊 Faible corrélation capacité/inscriptions : d\'autres facteurs (contenu, formateur) influencent la popularité.';
        }

        // Insight 4 — Comparaison populaires vs faibles
        $populaires = $segmentation['Populaire'];
        $faibles    = $segmentation['Faible'];

        if (!empty($populaires) && !empty($faibles)) {
            $avgPopulaire = array_sum(array_column($populaires, 'nbInscrits')) / count($populaires);
            $avgFaible    = array_sum(array_column($faibles, 'nbInscrits')) / count($faibles);
            $insights[] = sprintf(
                '🏆 Les formations populaires ont en moyenne %s inscrits vs %s pour les formations faibles.',
                round($avgPopulaire, 1),
                round($avgFaible, 1)
            );
        }

        // Insight 5 — Formations sans aucun inscrit
        $nbVidees = count(array_filter($nbParFormation, fn($nb) => $nb === 0));
        // Also count formations not in nbParFormation (0 inscriptions)
        $totalF = $statsGlobales['totalFormations'];
        $avecInscriptions = count($nbParFormation);
        $sansDonnees = $totalF - $avecInscriptions;
        $totalVides = $nbVidees + max(0, $sansDonnees);

        if ($totalVides > 0) {
            $insights[] = sprintf(
                '⚡ %d formation(s) n\'ont aucun inscrit. Revoir leur visibilité ou contenu.',
                $totalVides
            );
        }

        return $insights;
    }

    // ─────────────────────────────────────────────────────────────────
    // 12. Courbe de tendance des inscriptions par mois (6 derniers mois)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Retourne les inscriptions par mois sur les 6 derniers mois.
     * Basé sur date_inscription de inscriptions_formation.
     *
     * @return array{mois: string[], valeurs: int[]}
     */
    public function getTendanceInscriptionsParMois(): array
    {
        $conn = $this->em->getConnection();

        $sql = '
            SELECT
                YEAR(date_inscription)  AS annee,
                MONTH(date_inscription) AS mois,
                COUNT(*)                AS nb
            FROM inscriptions_formation
            WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(date_inscription), MONTH(date_inscription)
            ORDER BY annee ASC, mois ASC
        ';

        $rows = $conn->executeQuery($sql)->fetchAllAssociative();

        // Build a map keyed by "YYYY-MM"
        $map = [];
        foreach ($rows as $row) {
            $key = sprintf('%04d-%02d', (int)$row['annee'], (int)$row['mois']);
            $map[$key] = (int)$row['nb'];
        }

        // Fill all 6 months (including months with 0 inscriptions)
        $months = [];
        $values = [];
        $locale = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                   'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        for ($i = 5; $i >= 0; $i--) {
            $dt  = new \DateTime("first day of -$i month");
            $key = $dt->format('Y-m');
            $monthLabel = $locale[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
            $months[] = $monthLabel;
            $values[] = $map[$key] ?? 0;
        }

        return [
            'mois'    => $months,
            'valeurs' => $values,
        ];
    }
}
