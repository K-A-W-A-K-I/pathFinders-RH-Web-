<?php

namespace App\Controller\Offre;

use App\Entity\Offre;
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
        $isBlacklisted = $candidat && $candidat->isBlacklisted();
        $dejaPostule  = [];
        $recommandees = [];

        if ($candidat) {
            foreach ($offres as $o) {
                if ($candidatureRepo->dejaPostule($candidat->getId(), $o->getId())) {
                    $dejaPostule[$o->getId()] = true;
                }
            }

            // Recommandations uniquement sans filtre actif
            if (!$search && !$domaine && !$contrat) {
                $candidatures = $candidatureRepo->findByCandidat($candidat->getId());

                $domainesPostules = [];
                $contratsPostules = [];
                $offresDejaIds    = array_keys($dejaPostule);
                $cvScores         = [];

                foreach ($candidatures as $c) {
                    $domainesPostules[] = $c->getOffre()->getDomaine();
                    $contratsPostules[] = $c->getOffre()->getTypeContrat();
                    if ($c->getCvScoreIa() !== null) {
                        $cvScores[] = $c->getCvScoreIa();
                    }
                }

                $cvScoreMoyen = !empty($cvScores) ? (int) round(array_sum($cvScores) / count($cvScores)) : 0;

                if (!empty($domainesPostules)) {
                    $recommandees = $offreRepo->findRecommended(
                        array_unique($domainesPostules),
                        array_unique($contratsPostules),
                        $offresDejaIds,
                        $cvScoreMoyen,
                        4
                    );
                }
            }
        }

        return $this->render('candidature/offre_list.html.twig', [
            'offres'        => $offres,
            'dejaPostule'   => $dejaPostule,
            'isBlacklisted' => $isBlacklisted,
            'recommandees'  => $recommandees,
            'search'        => $search,
            'domaine'       => $domaine,
            'contrat'       => $contrat,
            'sort'          => $sort,
        ]);
    }

    /** Détail d'une offre (vue candidat) */
    #[Route('/offre/{id}', name: 'offre_detail', requirements: ['id' => '\d+'])]
    public function offreDetail(
        int $id,
        OffreRepository $offreRepo,
        CandidatureRepository $candidatureRepo,
        CandidatRepository $candidatRepo,
        SessionInterface $session
    ): Response {
        $offre = $offreRepo->find($id);
        if (!$offre || $offre->getStatut() !== 'active') {
            throw $this->createNotFoundException('Offre introuvable.');
        }

        $userId      = $session->get('user_id', 1);
        $candidat    = $candidatRepo->findByUserId($userId);
        $dejaPostule = $candidat && $candidatureRepo->dejaPostule($candidat->getId(), $id);
        $isBlacklisted = $candidat && $candidat->isBlacklisted();

        return $this->render('candidature/offre_detail.html.twig', [
            'offre'          => $offre,
            'dejaPostule'    => $dejaPostule,
            'isBlacklisted'  => $isBlacklisted,
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
