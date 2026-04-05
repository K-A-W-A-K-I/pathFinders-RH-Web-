<?php

namespace App\Controller\Dashboard;

use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
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
        InscriptionRepository $inscRepo
    ): Response {
        $allInscriptions = $inscRepo->findAll();

        return $this->render('dashboard/home.html.twig', [
            'nbCategories'    => count($catRepo->findAll()),
            'nbFormations'    => count($formRepo->findAll()),
            'nbInscriptions'  => count($allInscriptions),
            'categories'      => $catRepo->findAllSorted('nom', 'ASC'),
            'formations'      => $formRepo->findByCategorieAndSort(null, 'titre', 'ASC'),
            'allInscriptions' => $allInscriptions,
        ]);
    }
}
