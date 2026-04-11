<?php

namespace App\Controller\Formation;

use App\Repository\InscriptionRepository;
use App\Repository\InscriptionsFormationRepository;
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
        InscriptionRepository $inscRepo
    ): Response {
        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) { $session->start(); $sessionId = $session->getId(); }

        $user = $this->getUser();

        // ── Source 1 : inscriptions_formation (utilisateur connecté) ──
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

        // ── Source 2 : inscriptions (session, sans auth) ──
        $inscriptionsSession = $inscRepo->createQueryBuilder('i')
            ->innerJoin('i.formation', 'f')
            ->where('i.sessionId = :sid')
            ->setParameter('sid', $sessionId)
            ->orderBy('i.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();

        // Fusionner en évitant les doublons (même id_formation)
        $formationIds = array_map(fn($i) => $i->getFormation()->getIdFormation(), $inscriptionsForm);

        $unified = [];

        // Ajouter les inscriptions_formation avec progression réelle
        foreach ($inscriptionsForm as $i) {
            $f          = $i->getFormation();
            $totalMods  = $f->getContenuModules()->count();
            $modulesVus = $session->get('modules_vus_' . $f->getIdFormation(), []);
            $prog       = $totalMods > 0 ? round((count($modulesVus) / $totalMods) * 100) : (int)$i->getPourcentage_progression();

            $unified[] = [
                'formation'   => $f,
                'dateInscrit' => $i->getDate_inscription(),
                'progression' => (int)$prog,
                'source'      => 'db',
            ];
        }

        // Ajouter les inscriptions session non déjà présentes
        foreach ($inscriptionsSession as $i) {
            $f = $i->getFormation();
            if (in_array($f->getIdFormation(), $formationIds)) continue;

            $totalMods  = $f->getContenuModules()->count();
            $modulesVus = $session->get('modules_vus_' . $f->getIdFormation(), []);
            $prog       = $totalMods > 0 ? round((count($modulesVus) / $totalMods) * 100) : 0;

            $unified[] = [
                'formation'   => $f,
                'dateInscrit' => $i->getDateInscription(),
                'progression' => (int)$prog,
                'source'      => 'session',
            ];
        }

        $total    = count($unified);
        $termines = count(array_filter($unified, fn($i) => $i['progression'] === 100));
        $enCours  = count(array_filter($unified, fn($i) => $i['progression'] > 0 && $i['progression'] < 100));
        return $this->render('formation/mes_inscriptions.html.twig', [
            'inscriptions' => $unified,
            'stats' => [
                'total'      => $total,
                'termines'   => $termines,
                'enCours'    => $enCours,
                'nonDebutes' => $total - $termines - $enCours,
            ],
        ]);
    }
}
