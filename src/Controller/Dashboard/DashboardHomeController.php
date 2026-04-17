<?php

namespace App\Controller\Dashboard;

use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionsFormationRepository;
use App\Service\FormationStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard', name: 'dashboard_')]
class DashboardHomeController extends AbstractController
{
    #[Route('', name: 'home')]
    public function index(
        CategorieFormationRepository $catRepo,
        FormationRepository $formRepo,
        InscriptionsFormationRepository $inscRepo,
        FormationStatsService $statsService,
    ): Response {
        $allInscriptions = $inscRepo->findAllWithDetails();

        $topFormations   = $statsService->getTopFormations(5);
        $statsParCat     = $statsService->getStatsParCategorie();
        $tauxRemplissage = $statsService->getTauxRemplissageParFormation();
        $tendanceMois    = $statsService->getTendanceInscriptionsParMois();
        $progression     = $statsService->getProgressionMoyenneParFormation();
        $statsGlobales   = $statsService->getStatsGlobales();
        $interpretations = $statsService->getInterpretationsContextuelles();

        return $this->render('dashboard/home.html.twig', [
            'nbCategories'    => count($catRepo->findAll()),
            'nbFormations'    => count($formRepo->findAll()),
            'nbInscriptions'  => count($allInscriptions),
            'categories'      => $catRepo->findAllSorted('nom', 'ASC'),
            'formations'      => $formRepo->findByCategorieAndSort(null, 'titre', 'ASC'),
            'allInscriptions' => $allInscriptions,
            // Stats
            'statsGlobales'   => $statsGlobales,
            'topFormations'   => $topFormations,
            'statsParCat'     => $statsParCat,
            'tauxRemplissage' => $tauxRemplissage,
            'tendanceMois'    => $tendanceMois,
            'progression'     => $progression,
            'interpretations' => $interpretations,
        ]);
    }
}
