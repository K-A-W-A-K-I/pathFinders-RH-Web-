<?php

namespace App\Controller\Offre;

use App\Entity\Offre;
use App\Entity\Question;
use App\Form\OffreType;
use App\Form\QuestionType;
use App\Repository\CandidatRepository;
use App\Repository\CandidatureRepository;
use App\Repository\EntretienRepository;
use App\Repository\OffreRepository;
use App\Repository\QuestionRepository;
use App\Service\AiService;
use App\Service\CandidatureMailer;
use App\Service\ChartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/offres')]
class OffreController extends AbstractController
{
    #[Route('', name: 'offre_index')]
    public function index(OffreRepository $repo, CandidatureRepository $candidatureRepo, CandidatRepository $candidatRepo, QuestionRepository $questionRepo): Response
    {
        $offres        = $repo->findAll();
        $allCandidatures = $candidatureRepo->findAllWithRelations();
        $candidatRepo->hydrateNames(array_map(fn($c) => $c->getCandidat(), $allCandidatures));
        $allCandidatures = array_values(array_filter($allCandidatures, fn($c) => trim($c->getCandidat()->getFullName()) !== ''));

        $totalCv    = count($allCandidatures);
        $analysedCv = count(array_filter($allCandidatures, fn($c) => $c->getCvScoreIa() !== null));

        // Question stats
        $offresWithoutQuestions = array_values(array_filter($offres, fn($o) => $o->getQuestions()->count() === 0));
        $totalQuestions = array_sum(array_map(fn($o) => $o->getQuestions()->count(), $offres));

        return $this->render('offre/index.html.twig', [
            'offres'                  => $offres,
            'totalCv'                 => $totalCv,
            'analysedCv'              => $analysedCv,
            'offresWithoutQuestions'  => count($offresWithoutQuestions),
            'totalQuestions'          => $totalQuestions,
        ]);
    }

    #[Route('/candidatures', name: 'admin_candidatures')]
    public function adminCandidatures(
        Request $request,
        CandidatureRepository $candidatureRepo,
        CandidatRepository $candidatRepo,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): Response {
        $query = $candidatureRepo->findAllWithRelationsQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15  // 15 candidatures par page
        );

        // Hydrate names for current page items only
        $candidats = array_map(fn($c) => $c->getCandidat(), iterator_to_array($pagination));
        $candidatRepo->hydrateNames($candidats);

        // Filter out empty names
        $validIds = [];
        foreach ($pagination as $c) {
            if (trim($c->getCandidat()->getFullName()) !== '') {
                $validIds[] = $c->getId();
            }
        }

        return $this->render('offre/candidatures_admin.html.twig', [
            'candidatures' => $pagination,
        ]);
    }

    #[Route('/new', name: 'offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $offre = new Offre();
        $form  = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offre);
            $em->flush();
            $this->addFlash('success', 'Offre créée avec succès.');
            return $this->redirectToRoute('offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('offre/form.html.twig', [
            'form'  => $form,
            'offre' => $offre,
            'title' => 'Nouvelle offre',
        ]);
    }

    #[Route('/{id}', name: 'offre_show', requirements: ['id' => '\d+'])]
    public function show(
        Offre $offre,
        QuestionRepository $questionRepo,
        CandidatureRepository $candidatureRepo,
        EntretienRepository $entretienRepo,
        CandidatRepository $candidatRepo
    ): Response {
        $candidatures = $candidatureRepo->findByOffre($offre->getId());
        $candidatRepo->hydrateNames(array_map(fn($c) => $c->getCandidat(), $candidatures));
        $candidatures = array_values(array_filter($candidatures, fn($c) => trim($c->getCandidat()->getFullName()) !== ''));

        // Compute final score: 80% CV IA + 20% quiz, or 20% quiz only if no CV score
        $finalScores = [];
        foreach ($candidatures as $c) {
            $cvScore   = $c->getCvScoreIa();
            $quizScore = $c->getScore();
            $finalScores[$c->getId()] = $cvScore !== null
                ? (int) round($cvScore * 0.8 + $quizScore * 0.2)
                : (int) round($quizScore * 0.2);
        }

        // Sort by final score descending
        usort($candidatures, fn($a, $b) => $finalScores[$b->getId()] <=> $finalScores[$a->getId()]);

        $entretiens = $entretienRepo->findAllWithRelations();
        $entretiens = array_filter($entretiens, fn($e) => $e->getOffre()->getId() === $offre->getId());

        return $this->render('offre/show.html.twig', [
            'offre'        => $offre,
            'questions'    => $questionRepo->findByOffre($offre->getId()),
            'candidatures' => $candidatures,
            'finalScores'  => $finalScores,
            'entretiens'   => $entretiens,
        ]);
    }

    #[Route('/{id}/edit', name: 'offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Offre modifiée.');
            return $this->redirectToRoute('offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('offre/form.html.twig', [
            'form'  => $form,
            'offre' => $offre,
            'title' => 'Modifier l\'offre',
        ]);
    }

    #[Route('/{id}/delete', name: 'offre_delete', methods: ['POST'])]
    public function delete(Offre $offre, EntityManagerInterface $em): Response
    {
        $em->remove($offre);
        $em->flush();
        $this->addFlash('success', 'Offre supprimée.');
        return $this->redirectToRoute('offre_index');
    }

    // ── Questions ──────────────────────────────────────────────────────────

    #[Route('/{id}/questions/new', name: 'question_new', methods: ['GET', 'POST'])]
    public function newQuestion(Request $request, Offre $offre, EntityManagerInterface $em): Response
    {
        $question = new Question();
        $question->setOffre($offre);
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Question ajoutée.');
            return $this->redirectToRoute('offre_show', ['id' => $offre->getId(), '_fragment' => 'questions']);
        }

        return $this->render('offre/question_form.html.twig', [
            'form'  => $form,
            'offre' => $offre,
            'title' => 'Nouvelle question',
        ]);
    }

    #[Route('/questions/{id}/edit', name: 'question_edit', methods: ['GET', 'POST'])]
    public function editQuestion(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Question modifiée.');
            return $this->redirectToRoute('offre_show', ['id' => $question->getOffre()->getId(), '_fragment' => 'questions']);
        }

        return $this->render('offre/question_form.html.twig', [
            'form'  => $form,
            'offre' => $question->getOffre(),
            'title' => 'Modifier la question',
        ]);
    }

    #[Route('/questions/{id}/delete', name: 'question_delete', methods: ['POST'])]
    public function deleteQuestion(Question $question, EntityManagerInterface $em): Response
    {
        $offreId = $question->getOffre()->getId();
        $em->remove($question);
        $em->flush();
        $this->addFlash('success', 'Question supprimée.');
        return $this->redirectToRoute('offre_show', ['id' => $offreId, '_fragment' => 'questions']);
    }

    // ── Candidatures admin actions ─────────────────────────────────────────

    #[Route('/admin/candidatures/{id}/statut/{statut}', name: 'candidature_statut', methods: ['POST'])]
    public function updateStatut(
        int $id,
        int $statut,
        Request $request,
        CandidatureRepository $repo,
        CandidatRepository $candidatRepo,
        EntityManagerInterface $em,
        CandidatureMailer $mailer
    ): Response {
        $candidature = $repo->find($id);
        if ($candidature) {
            $candidature->setStatutAdmin($statut);
            $candidature->setAdmis($statut === 1);

            // Si on accepte, lever le blacklist automatiquement
            if ($statut === 1) {
                $candidat = $candidature->getCandidat();
                if ($candidat->isBlacklisted()) {
                    $candidat->setIsBlacklisted(false);
                    $candidat->setBlacklistNote(null);
                    $candidat->setBlacklistedAt(null);
                    $this->addFlash('success', 'Candidature acceptée et blacklist levé.');
                } else {
                    $this->addFlash('success', 'Candidature acceptée.');
                }
            } else {
                $this->addFlash('success', 'Statut mis à jour.');
            }

            $em->flush();

            // Envoyer email au candidat
            try {
                $candidat = $candidature->getCandidat();
                $candidatRepo->hydrateNames([$candidat]);
                $email = $candidat->getEmail();
                $nom   = $candidat->getFullName() ?: 'Candidat';
                if ($email) {
                    if ($statut === 1) {
                        $mailer->sendCandidatureAcceptee($candidature, $email, $nom);
                    } elseif ($statut === -1) {
                        $mailer->sendCandidatureRefusee($candidature, $email, $nom);
                    }
                }
            } catch (\Throwable) {
                // Ne pas bloquer si l'email échoue
            }
        }
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('admin_candidatures');
    }

    #[Route('/admin/candidatures/{id}/blacklist', name: 'candidature_blacklist', methods: ['POST'])]
    public function blacklistCandidat(
        int $id,
        Request $request,
        CandidatureRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $candidature = $repo->find($id);
        if ($candidature) {
            // Refuser la candidature
            $candidature->setStatutAdmin(-1);
            $candidature->setAdmis(false);

            // Blacklister le candidat
            $candidat = $candidature->getCandidat();
            $note = trim($request->request->get('blacklist_note', ''));
            $candidat->setIsBlacklisted(true);
            $candidat->setBlacklistNote($note ?: null);
            $candidat->setBlacklistedAt(new \DateTime());

            $em->flush();
            $this->addFlash('warning', 'Candidature refusée et candidat blacklisté.');
        }
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('admin_candidatures');
    }

    // ── AI: Bulk generate questions for offres without questions ──────────

    #[Route('/bulk-generate-questions', name: 'offre_bulk_generate_questions', methods: ['POST'])]
    public function bulkGenerateQuestions(
        OffreRepository $repo,
        AiService $ai,
        EntityManagerInterface $em
    ): JsonResponse {
        $offres = $repo->findAll();
        $offresWithout = array_values(array_filter($offres, fn($o) => $o->getQuestions()->count() === 0));

        if (empty($offresWithout)) {
            return new JsonResponse(['success' => true, 'count' => 0, 'message' => 'Toutes les offres ont déjà des questions.']);
        }

        $totalSaved = 0;
        $errors = [];

        foreach ($offresWithout as $offre) {
            try {
                $generated = $ai->generateQuestions($offre->getTitre(), $offre->getDescription(), $offre->getDomaine(), 5);
                foreach ($generated as $item) {
                    $q = new Question();
                    $q->setOffre($offre);
                    $q->setQuestion($item['question'] ?? '');
                    $q->setChoix1($item['choix'][0] ?? 'A');
                    $q->setChoix2($item['choix'][1] ?? 'B');
                    $q->setChoix3($item['choix'][2] ?? null);
                    $q->setChoix4($item['choix'][3] ?? null);
                    $q->setBonneReponse((int) ($item['bonne_reponse'] ?? 1));
                    $q->setPoints((int) ($item['points'] ?? 1));
                    $em->persist($q);
                    $totalSaved++;
                }
                $em->flush();
            } catch (\Throwable $e) {
                $errors[] = $offre->getTitre() . ': ' . $e->getMessage();
            }
        }

        return new JsonResponse([
            'success'   => true,
            'count'     => count($offresWithout),
            'questions' => $totalSaved,
            'errors'    => $errors,
        ]);
    }

    // ── AI: Generate questions ─────────────────────────────────────────────

    #[Route('/{id}/generate-questions', name: 'offre_generate_questions', methods: ['POST'])]
    public function generateQuestions(
        Offre $offre,
        Request $request,
        AiService $ai,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $count     = (int) ($request->request->get('count', 5));
            $count     = max(1, min(10, $count));
            $generated = $ai->generateQuestions($offre->getTitre(), $offre->getDescription(), $offre->getDomaine(), $count);

            $saved = 0;
            foreach ($generated as $item) {
                $q = new Question();
                $q->setOffre($offre);
                $q->setQuestion($item['question'] ?? '');
                $q->setChoix1($item['choix'][0] ?? 'A');
                $q->setChoix2($item['choix'][1] ?? 'B');
                $q->setChoix3($item['choix'][2] ?? null);
                $q->setChoix4($item['choix'][3] ?? null);
                $q->setBonneReponse((int) ($item['bonne_reponse'] ?? 1));
                $q->setPoints((int) ($item['points'] ?? 1));
                $em->persist($q);
                $saved++;
            }
            $em->flush();

            return new JsonResponse(['success' => true, 'count' => $saved]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── AI: Analyse CV ────────────────────────────────────────────────────

    #[Route('/candidature/{id}/analyse-cv', name: 'candidature_analyse_cv', methods: ['POST'])]
    public function analyseCv(
        int $id,
        AiService $ai,
        CandidatureRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $candidature = $repo->find($id);
        if (!$candidature) {
            return new JsonResponse(['success' => false, 'error' => 'Candidature introuvable'], 404);
        }

        $cvPath = $candidature->getCandidat()->getCvPath();
        if (!$cvPath) {
            return new JsonResponse(['success' => false, 'error' => 'Aucun CV disponible pour ce candidat'], 400);
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/public/uploads/cv/' . basename($cvPath);
        if (!file_exists($fullPath)) {
            return new JsonResponse(['success' => false, 'error' => 'Fichier CV introuvable sur le serveur'], 404);
        }

        try {
            // Extract text from PDF using smalot/pdfparser
            $parser  = new \Smalot\PdfParser\Parser();
            $pdf     = $parser->parseFile($fullPath);
            $cvText  = $pdf->getText();

            if (empty(trim($cvText))) {
                return new JsonResponse(['success' => false, 'error' => 'Impossible d\'extraire le texte du CV (PDF scanné ?)'], 400);
            }

            $offre  = $candidature->getOffre();
            $result = $ai->analyseCv($cvText, $offre->getTitre(), $offre->getDescription(), $offre->getDomaine());

            $candidature->getCandidat()->setCvScoreIa($result['score']);
            $candidature->getCandidat()->setCvScoreDetails($result['details']);
            $candidature->getCandidat()->setCvAnalyseDate(new \DateTime());
            $candidature->setCvScoreIa($result['score']);
            $em->flush();

            return new JsonResponse(['success' => true, 'score' => $result['score'], 'details' => $result['details']]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
