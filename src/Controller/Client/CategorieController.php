<?php

namespace App\Controller\Client;

use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formations', name: 'client_')]
class CategorieController extends AbstractController
{
    #[Route('', name: 'categories')]
    public function index(Request $request, CategorieFormationRepository $repo, FormationRepository $formationRepo): Response
    {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'nom');
        $dir  = $request->query->get('dir', 'ASC');

        $categories = $q
            ? $repo->search($q, $sort, $dir)
            : $repo->findAllSorted($sort, $dir);

        // Récupérer toutes les formations avec date_debut pour le calendrier
        $allFormations = $formationRepo->findAll();
        $calendarEvents = [];
        foreach ($allFormations as $f) {
            if ($f->getDateDebut()) {
                $calendarEvents[$f->getDateDebut()->format('Y-m-d')][] = [
                    'titre' => $f->getTitre(),
                    'id'    => $f->getIdFormation(),
                ];
            }
        }

        return $this->render('client/categories/index.html.twig', [
            'categories'     => $categories,
            'q'              => $q,
            'sort'           => $sort,
            'dir'            => $dir,
            'calendarEvents' => $calendarEvents,
        ]);
    }
}