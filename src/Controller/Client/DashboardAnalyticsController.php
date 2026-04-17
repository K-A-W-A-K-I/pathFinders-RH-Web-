<?php

namespace App\Controller\Client;

use App\Repository\InscriptionsFormationRepository;
use App\Repository\InscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardAnalyticsController extends AbstractController
{
    #[Route('/dashboard/analytics', name: 'client_dashboard_analytics')]
    public function index(
        Request $request,
        InscriptionsFormationRepository $inscFormRepo,
        InscriptionRepository $inscRepo
    ): Response {
        $user    = $this->getUser();
        $session = $request->getSession();

        // ── 1. Inscriptions de l'utilisateur connecté (inscriptions_formation) ──
        $inscriptions = [];
        if ($user) {
            $inscriptions = $inscFormRepo->createQueryBuilder('i')
                ->innerJoin('i.formation', 'f')
                ->leftJoin('f.categorie', 'c')
                ->where('i.utilisateur = :user')
                ->setParameter('user', $user)
                ->orderBy('i.pourcentage_progression', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // ── 2. Données pour graphique progression par formation ──
        $progressionLabels = [];
        $progressionData   = [];
        $progressionColors = [];

        foreach ($inscriptions as $i) {
            $f    = $i->getFormation();
            $prog = (int)$i->getPourcentage_progression();

            // Enrichir depuis session si plus élevé
            $sessionProg = 0;
            if ($f) {
                $totalMods  = $f->getContenuModules()->count();
                $modulesVus = $session->get('modules_vus_' . $f->getIdFormation(), []);
                $sessionProg = $totalMods > 0 ? (int)round((count($modulesVus) / $totalMods) * 100) : 0;
            }
            $prog = max($prog, $sessionProg);

            $progressionLabels[] = $f ? mb_substr($f->getTitre(), 0, 25) : 'Formation';
            $progressionData[]   = $prog;
            $progressionColors[] = $prog === 100 ? '#1D9E75' : ($prog > 0 ? '#F59E0B' : '#E24B4A');
        }

        // ── 3. Taux de complétion global ──
        $total      = count($inscriptions);
        $termines   = count(array_filter($inscriptions, fn($i) => (int)$i->getPourcentage_progression() >= 100));
        $enCours    = count(array_filter($inscriptions, fn($i) => (int)$i->getPourcentage_progression() > 0 && (int)$i->getPourcentage_progression() < 100));
        $nonDebutes = $total - $termines - $enCours;
        $tauxCompletion = $total > 0 ? round(($termines / $total) * 100) : 0;

        // ── 4. Top formations les plus populaires (global) ──
        $topFormations = $inscFormRepo->createQueryBuilder('i')
            ->select('f.titre, COUNT(i.id_inscription) as nbInscrits')
            ->innerJoin('i.formation', 'f')
            ->groupBy('f.idFormation')
            ->orderBy('nbInscrits', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Ajouter aussi depuis inscriptions (table session)
        $topFormationsSession = $inscRepo->createQueryBuilder('i')
            ->select('f.titre, COUNT(i.id) as nbInscrits')
            ->innerJoin('i.formation', 'f')
            ->groupBy('f.idFormation')
            ->orderBy('nbInscrits', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Fusionner les deux sources
        $topMerged = [];
        foreach (array_merge($topFormations, $topFormationsSession) as $row) {
            $titre = $row['titre'];
            if (!isset($topMerged[$titre])) $topMerged[$titre] = 0;
            $topMerged[$titre] += (int)$row['nbInscrits'];
        }
        arsort($topMerged);
        $topMerged = array_slice($topMerged, 0, 5, true);

        // ── 5. Heures totales ──
        $totalHeures = 0;
        foreach ($inscriptions as $i) {
            if ($i->getFormation() && $i->getFormation()->getDureeHeures()) {
                $totalHeures += (float)$i->getFormation()->getDureeHeures();
            }
        }

        return $this->render('client/dashboard_analytics.html.twig', [
            'inscriptions'       => $inscriptions,
            'progressionLabels'  => $progressionLabels,
            'progressionData'    => $progressionData,
            'progressionColors'  => $progressionColors,
            'total'              => $total,
            'termines'           => $termines,
            'enCours'            => $enCours,
            'nonDebutes'         => $nonDebutes,
            'tauxCompletion'     => $tauxCompletion,
            'topFormations'      => $topMerged,
            'totalHeures'        => $totalHeures,
        ]);
    }
}
