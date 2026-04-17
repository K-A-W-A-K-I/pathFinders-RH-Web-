<?php

namespace App\Controller\Formation;

use App\Chart\ProgressionFormationsChart;
use App\Chart\TopFormationsChart;
use App\Repository\InscriptionRepository;
use App\Repository\InscriptionsFormationRepository;
use Mukadi\ChartJSBundle\Factory\ChartFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MesInscriptionsFormationController extends AbstractController
{
    #[Route('/mes-inscriptions-formation', name: 'mes_inscriptions_formation')]
    public function index(
        Request $request,
        InscriptionsFormationRepository $inscFormRepo,
        InscriptionRepository $inscRepo,
        ChartFactory $chartFactory
    ): Response {
        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) { $session->start(); $sessionId = $session->getId(); }

        $user = $this->getUser();

        // ── Source 1 : inscriptions_formation ──
        $inscriptionsForm = [];
        if ($user) {
            $inscriptionsForm = $inscFormRepo->createQueryBuilder('i')
                ->innerJoin('i.formation', 'f')
                ->where('i.utilisateur = :user')
                ->setParameter('user', $user)
                ->orderBy('i.date_inscription', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // ── Source 2 : inscriptions (session) ──
        $inscriptionsSession = $inscRepo->createQueryBuilder('i')
            ->innerJoin('i.formation', 'f')
            ->where('i.sessionId = :sid')
            ->setParameter('sid', $sessionId)
            ->orderBy('i.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();

        $formationIds = array_map(fn($i) => $i->getFormation()->getIdFormation(), $inscriptionsForm);
        $unified = [];

        foreach ($inscriptionsForm as $i) {
            $f           = $i->getFormation();
            $totalMods   = $f->getContenuModules()->count();
            $modulesVus  = $session->get('modules_vus_' . $f->getIdFormation(), []);
            $progBase    = (int)$i->getPourcentage_progression();
            $progSession = $totalMods > 0 ? (int)round((count($modulesVus) / $totalMods) * 100) : 0;
            $prog        = max($progBase, $progSession);

            $unified[] = [
                'formation'   => $f,
                'dateInscrit' => $i->getDate_inscription(),
                'progression' => $prog,
                'source'      => 'db',
            ];
        }

        foreach ($inscriptionsSession as $i) {
            $f = $i->getFormation();
            if (in_array($f->getIdFormation(), $formationIds)) continue;

            $totalMods  = $f->getContenuModules()->count();
            $modulesVus = $session->get('modules_vus_' . $f->getIdFormation(), []);
            $prog       = $totalMods > 0 ? (int)round((count($modulesVus) / $totalMods) * 100) : 0;

            $unified[] = [
                'formation'   => $f,
                'dateInscrit' => $i->getDateInscription(),
                'progression' => (int)$prog,
                'source'      => 'session',
            ];
        }

        // ── Stats ──
        $total      = count($unified);
        $termines   = count(array_filter($unified, fn($i) => $i['progression'] === 100));
        $enCours    = count(array_filter($unified, fn($i) => $i['progression'] > 0 && $i['progression'] < 100));
        $nonDebutes = $total - $termines - $enCours;
        $tauxCompletion = $total > 0 ? round(($termines / $total) * 100) : 0;

        $totalHeures = 0;
        foreach ($unified as $ins) {
            $f = $ins['formation'];
            if ($f && $f->getDureeHeures()) $totalHeures += (float)$f->getDureeHeures();
        }

        // ── Charts via mukadi/chartjs-bundle ──
        $chartProgression = null;
        $chartTop         = null;
        $chartDonutData   = [$termines, $enCours, $nonDebutes];

        if ($user && $total > 0) {
            // Graphique progression par formation (DQL)
            $chartProgression = $chartFactory
                ->withDql()
                ->createFromDefinition(ProgressionFormationsChart::class)
                ->setParameter(':userId', $user->getId())
                ->getChart();

            // Top formations populaires (DQL)
            $chartTop = $chartFactory
                ->withDql()
                ->createFromDefinition(TopFormationsChart::class)
                ->getChart();
        }

        // Donut : données simples passées au template
        $topFormations    = [];
        $topLabels        = [];
        $topData          = [];

        $topRows = $inscFormRepo->createQueryBuilder('i')
            ->select('f.titre, COUNT(i.id_inscription) as nbInscrits')
            ->innerJoin('i.formation', 'f')
            ->groupBy('f.idFormation')
            ->orderBy('nbInscrits', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        foreach ($topRows as $row) {
            $topFormations[$row['titre']] = (int)$row['nbInscrits'];
        }
        $topLabels = array_keys($topFormations);
        $topData   = array_values($topFormations);

        // Data pour graphique progression (fallback si non connecté)
        $progressionLabels = [];
        $progressionData   = [];
        $progressionColors = [];
        foreach ($unified as $ins) {
            $f    = $ins['formation'];
            $prog = $ins['progression'];
            $progressionLabels[] = $f ? mb_substr($f->getTitre(), 0, 15) . (mb_strlen($f->getTitre()) > 15 ? '…' : '') : 'Formation';
            $progressionData[]   = $prog;
            $progressionColors[] = $prog === 100 ? '#1D9E75' : ($prog > 0 ? '#F59E0B' : '#E24B4A');
            if ($f && $f->getDureeHeures()) $totalHeures += 0; // already counted
        }

        return $this->render('formation/mes_inscriptions.html.twig', [
            'inscriptions'       => $unified,
            'stats' => [
                'total'      => $total,
                'termines'   => $termines,
                'enCours'    => $enCours,
                'nonDebutes' => $nonDebutes,
            ],
            'tauxCompletion'     => $tauxCompletion,
            'totalHeures'        => $totalHeures,
            // mukadi charts (DQL-based, user connecté)
            'chartProgression'   => $chartProgression,
            'chartTop'           => $chartTop,
            'chartDonutData'     => $chartDonutData,
            // fallback data (session users)
            'progressionLabels'  => $progressionLabels,
            'progressionData'    => $progressionData,
            'progressionColors'  => $progressionColors,
            'topLabels'          => $topLabels,
            'topData'            => $topData,
            'topFormations'      => $topFormations,
        ]);
    }
}
