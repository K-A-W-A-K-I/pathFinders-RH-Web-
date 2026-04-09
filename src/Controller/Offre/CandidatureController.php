<?php

namespace App\Controller\Offre;

use App\Repository\CandidatRepository;
use App\Repository\CandidatureRepository;
use App\Repository\EntretienRepository;
use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class CandidatureController extends AbstractController
{
    /** Liste des offres actives (vue candidat) */
    #[Route('/', name: 'offre_list')]
    public function offreList(
        Request $request,
        OffreRepository $offreRepo,
        CandidatureRepository $candidatureRepo,
        CandidatRepository $candidatRepo,
        SessionInterface $session
    ): Response {
        $search  = $request->query->get('search');
        $domaine = $request->query->get('domaine');
        $contrat = $request->query->get('contrat');
        $sort    = $request->query->get('sort');

        $offres = $offreRepo->findFiltered($search, $domaine, $contrat, $sort);

        // Offres déjà postulées par le candidat courant
        $userId   = $session->get('user_id', 1);
        $candidat = $candidatRepo->findByUserId($userId);
        $dejaPostule = [];
        if ($candidat) {
            foreach ($offres as $o) {
                if ($candidatureRepo->dejaPostule($candidat->getId(), $o->getId())) {
                    $dejaPostule[$o->getId()] = true;
                }
            }
        }

        return $this->render('candidature/offre_list.html.twig', [
            'offres'      => $offres,
            'dejaPostule' => $dejaPostule,
            'search'      => $search,
            'domaine'     => $domaine,
            'contrat'     => $contrat,
            'sort'        => $sort,
        ]);
    }

    /** Mes candidatures (vue candidat) */
    #[Route('/mes-candidatures', name: 'candidature_mes')]
    public function mesCandidatures(
        CandidatureRepository $candidatureRepo,
        CandidatRepository $candidatRepo,
        EntretienRepository $entretienRepo,
        SessionInterface $session
    ): Response {
        $userId   = $session->get('user_id', 1);
        $candidat = $candidatRepo->findByUserId($userId);

        if (!$candidat) {
            return $this->render('candidature/mes_candidatures.html.twig', [
                'candidatures' => [],
                'stats'        => ['total' => 0, 'accepte' => 0, 'refuse' => 0, 'attente' => 0],
                'entretiens'   => [],
            ]);
        }

        $candidatures = $candidatureRepo->findByCandidat($candidat->getId());
        $entretiens   = $entretienRepo->findByCandidat($candidat->getId());

        // Index entretiens par offre id
        $entretienByOffre = [];
        foreach ($entretiens as $e) {
            $entretienByOffre[$e->getOffre()->getId()] = $e;
        }

        $stats = [
            'total'   => count($candidatures),
            'accepte' => count(array_filter($candidatures, fn($c) => $c->getStatutAdmin() === 1)),
            'refuse'  => count(array_filter($candidatures, fn($c) => $c->getStatutAdmin() === -1)),
            'attente' => count(array_filter($candidatures, fn($c) => $c->getStatutAdmin() === 0)),
        ];

        return $this->render('candidature/mes_candidatures.html.twig', [
            'candidatures'    => $candidatures,
            'stats'           => $stats,
            'entretienByOffre'=> $entretienByOffre,
        ]);
    }
}
