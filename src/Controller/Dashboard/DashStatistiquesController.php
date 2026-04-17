<?php

namespace App\Controller\Dashboard;

use App\Repository\FormationRepository;
use App\Service\FormationStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashStatistiquesController extends AbstractController
{
    #[Route('/dashboard/statistiques', name: 'dashboard_statistiques')]
    public function index(
        FormationStatsService $statsService,
        FormationRepository $formationRepo,
    ): Response {
        return $this->render('dashboard/statistiques.html.twig', [
            'statsGlobales'   => $statsService->getStatsGlobales(),
            'statsParCat'     => $statsService->getStatsParCategorie(),
            'topFormations'   => $statsService->getTopFormations(5),
            'topParCat'       => $statsService->getTopFormationParCategorie(),
            'progMoyenne'     => $statsService->getProgressionMoyenneParFormation(),
            'tauxRemplissage' => $statsService->getTauxRemplissageParFormation(),
            'nbInscrits'      => $statsService->getNbInscriptionsParFormation(),
            'formations'      => $formationRepo->findByCategorieAndSort(null, 'titre', 'ASC'),
            'formationsJs'    => array_map(fn($f) => [
                'id'          => $f->getIdFormation(),
                'titre'       => $f->getTitre(),
                'capaciteMax' => $f->getCapaciteMax(),
            ], $formationRepo->findByCategorieAndSort(null, 'titre', 'ASC')),
            'correlation'     => $statsService->getCorrelationCapaciteInscriptions(),
            'regression'      => $statsService->getRegressionsLineaire(),
            'segmentation'    => $statsService->getSegmentationFormations(),
            'interpretations' => $statsService->getInterpretationsContextuelles(),
            'tendanceMois'    => $statsService->getTendanceInscriptionsParMois(),
        ]);
    }
}
