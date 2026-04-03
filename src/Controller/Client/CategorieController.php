<?php

namespace App\Controller\Client;

use App\Repository\CategorieFormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formations', name: 'client_')]
class CategorieController extends AbstractController
{
    #[Route('', name: 'categories')]
    public function index(Request $request, CategorieFormationRepository $repo): Response
    {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'nom');
        $dir  = $request->query->get('dir', 'ASC');

        // Debug: afficher les paramètres reçus
        if ($sort !== 'nom' || $dir !== 'ASC' || $q !== '') {
            $this->addFlash('info', "Tri: $sort | Direction: $dir | Recherche: " . ($q ?: 'aucune'));
        }

        $categories = $q
            ? $repo->search($q, $sort, $dir)
            : $repo->findAllSorted($sort, $dir);

        return $this->render('client/categories/index.html.twig', [
            'categories' => $categories,
            'q'          => $q,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }
}