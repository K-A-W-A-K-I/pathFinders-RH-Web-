<?php

namespace App\Controller\Offre;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Repository\CandidatRepository;
use App\Repository\CandidatureRepository;
use App\Repository\OffreRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    /**
     * Démarre le quiz pour une offre donnée.
     * En production, l'utilisateur connecté serait récupéré via Security.
     * Ici on utilise la session pour simuler un candidat (id_utilisateur = 1 par défaut).
     */
    #[Route('/{id}', name: 'quiz_start')]
    public function start(
        int $id,
        OffreRepository $offreRepo,
        QuestionRepository $questionRepo,
        CandidatureRepository $candidatureRepo,
        CandidatRepository $candidatRepo,
        SessionInterface $session
    ): Response {
        $offre = $offreRepo->find($id);
        if (!$offre || $offre->getStatut() !== 'active') {
            throw $this->createNotFoundException('Offre introuvable.');
        }

        // Récupère ou crée le candidat pour l'utilisateur en session
        $userId   = $session->get('user_id', 1);
        $candidat = $candidatRepo->findByUserId($userId);

        // Vérifier si le candidat est blacklisté
        if ($candidat && $candidat->isBlacklisted()) {
            $this->addFlash('danger', 'Votre compte ne vous permet pas de postuler à des offres.');
            return $this->redirectToRoute('offre_list');
        }

        if ($candidat && $candidatureRepo->dejaPostule($candidat->getId(), $id)) {
            $this->addFlash('warning', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('offre_list');
        }

        $questions = $questionRepo->findByOffre($id);
        if (empty($questions)) {
            $this->addFlash('warning', 'Aucune question disponible pour cette offre.');
            return $this->redirectToRoute('offre_list');
        }

        // Stocke les données du quiz en session
        $session->set('quiz_offre_id', $id);
        $session->set('quiz_start_time', time());

        return $this->render('quiz/index.html.twig', [
            'offre'     => $offre,
            'questions' => $questions,
        ]);
    }

    #[Route('/{id}/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submit(
        int $id,
        Request $request,
        OffreRepository $offreRepo,
        QuestionRepository $questionRepo,
        CandidatRepository $candidatRepo,
        CandidatureRepository $candidatureRepo,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        $offre     = $offreRepo->find($id);
        $questions = $questionRepo->findByOffre($id);

        if (!$offre || empty($questions)) {
            return $this->redirectToRoute('offre_list');
        }

        // Calcul du score
        $answers   = $request->request->all('answers') ?? [];
        $total     = 0;
        $maxScore  = 0;

        foreach ($questions as $q) {
            $maxScore += $q->getPoints();
            $userAnswer = (int) ($answers[$q->getId()] ?? 0);
            if ($userAnswer === $q->getBonneReponse()) {
                $total += $q->getPoints();
            }
        }

        $pct = $maxScore > 0 ? (int) round(($total / $maxScore) * 100) : 0;

        // Récupère ou crée le candidat
        $userId   = $session->get('user_id', 1);
        $candidat = $candidatRepo->findByUserId($userId);

        // Bloquer si blacklisté
        if ($candidat && $candidat->isBlacklisted()) {
            $this->addFlash('danger', 'Votre compte ne vous permet pas de postuler à des offres.');
            return $this->redirectToRoute('offre_list');
        }

        if (!$candidat) {
            $candidat = new Candidat();
            $candidat->setIdUtilisateur($userId);
            $em->persist($candidat);
        }

        // Sauvegarde la candidature
        $candidature = new Candidature();
        $candidature->setOffre($offre);
        $candidature->setCandidat($candidat);
        $candidature->setScore($pct);
        $candidature->setAdmis(false);
        $em->persist($candidature);
        $em->flush();

        $session->set('last_candidature_id', $candidature->getId());
        $session->set('last_score', $pct);
        $session->set('last_offre_id', $id);

        return $this->redirectToRoute('quiz_result', [
            'id'    => $id,
            'score' => $pct,
            'cid'   => $candidature->getId(),
        ]);
    }

    #[Route('/{id}/result', name: 'quiz_result')]
    public function result(
        int $id,
        Request $request,
        OffreRepository $offreRepo
    ): Response {
        $offre = $offreRepo->find($id);
        $score = (int) $request->query->get('score', 0);
        $cid   = (int) $request->query->get('cid', 0);
        $admis = $score >= $offre->getScoreMinimum();

        return $this->render('quiz/result.html.twig', [
            'offre'        => $offre,
            'score'        => $score,
            'admis'        => $admis,
            'candidatureId'=> $cid,
        ]);
    }
}
