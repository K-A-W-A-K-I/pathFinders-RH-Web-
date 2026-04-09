<?php

namespace App\Controller\User;

use App\Repository\CategorieEvenementRepository;
use App\Repository\EvenementRepository;
use App\Repository\FavoriteRepository;
use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/espace-employe', name: 'worker_')]
class WorkerController extends AbstractController
{
    #[Route('/formations', name: 'formations')]
    public function formations(
        \App\Repository\CategorieFormationRepository $catRepo,
        \Symfony\Component\HttpFoundation\Request $request
    ): Response {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'nom');
        $dir  = $request->query->get('dir', 'ASC');
        $categories = $catRepo->findAllSorted($sort, $dir);

        if ($q) {
            $categories = array_filter($categories, fn($c) => stripos($c->getNomCategorie(), $q) !== false);
        }

        return $this->render('worker/formations.html.twig', [
            'categories' => $categories,
            'q'          => $q,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    #[Route('/formations/categorie/{id}', name: 'formations_by_categorie')]
    public function formationsByCategorie(
        int $id,
        \App\Repository\CategorieFormationRepository $catRepo,
        \App\Repository\FormationRepository $formRepo,
        \Symfony\Component\HttpFoundation\Request $request
    ): Response {
        $categorie = $catRepo->find($id);
        if (!$categorie) throw $this->createNotFoundException();

        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'titre');
        $dir  = $request->query->get('dir', 'ASC');

        $formations = $q
            ? $formRepo->search($q, $id, $sort, $dir)
            : $formRepo->findByCategorieAndSort($id, $sort, $dir);

        return $this->render('worker/formations_list.html.twig', [
            'categorie'  => $categorie,
            'formations' => $formations,
            'q'          => $q,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    #[Route('/mes-formations', name: 'mes_formations')]
    public function mesFormations(InscriptionRepository $inscRepo): Response
    {
        $user         = $this->getUser();
        $inscriptions = [];

        if ($user && method_exists($user, 'getEmail')) {
            $inscriptions = $inscRepo->createQueryBuilder('i')
                ->where('i.emailParticipant = :email')
                ->setParameter('email', $user->getEmail())
                ->orderBy('i.dateInscription', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('worker/mes_formations.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/evenements', name: 'evenements')]
    public function evenements(
        Request $request,
        EvenementRepository $evenementRepo,
        CategorieEvenementRepository $categorieRepo,
        FavoriteRepository $favoriteRepo,
        Connection $connection
    ): Response {
        $search      = trim((string) $request->query->get('search', ''));
        $categorieId = is_numeric($request->query->get('categorie')) ? (int)$request->query->get('categorie') : null;
        $currentUserId = null;
        $user = $this->getUser();
        if ($user && method_exists($user, 'getId')) {
            $currentUserId = $user->getId();
        }

        $evenements    = $evenementRepo->findByFilters($search ?: null, null, $categorieId, null);
        $favoriteIds   = $currentUserId ? $favoriteRepo->findEventIdsByUserId($currentUserId) : [];
        $registeredIds = $currentUserId ? array_map('intval', $connection->fetchFirstColumn(
            'SELECT id_evenement FROM inscription_evenement WHERE id_utilisateur = ?', [$currentUserId]
        )) : [];

        return $this->render('worker/evenements.html.twig', [
            'evenements'    => $evenements,
            'categories'    => $categorieRepo->findAllOrdered(),
            'search'        => $search,
            'categorieId'   => $categorieId,
            'favoriteIds'   => $favoriteIds,
            'favoriteCount' => count($favoriteIds),
            'registeredIds' => $registeredIds,
            'currentUserId' => $currentUserId,
        ]);
    }

    #[Route('/a-propos', name: 'about')]
    public function about(): Response
    {
        return $this->render('worker/about.html.twig');
    }
}
