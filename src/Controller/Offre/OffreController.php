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
    public function index(OffreRepository $repo): Response
    {
        return $this->render('offre/index.html.twig', [
            'offres' => $repo->findAll(),
        ]);
    }

    #[Route('/candidatures', name: 'admin_candidatures')]
    public function adminCandidatures(CandidatureRepository $candidatureRepo, \App\Repository\CandidatRepository $candidatRepo): Response
    {
        $candidatures = $candidatureRepo->findAllWithRelations();
        $candidats = array_map(fn($c) => $c->getCandidat(), $candidatures);
        $candidatRepo->hydrateNames($candidats);

        $candidatures = array_values(array_filter($candidatures, fn($c) => trim($c->getCandidat()->getFullName()) !== ''));

        return $this->render('offre/candidatures_admin.html.twig', [
            'candidatures' => $candidatures,
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

    #[Route('/{id}', name: 'offre_show')]
    public function show(
        Offre $offre,
        QuestionRepository $questionRepo,
        CandidatureRepository $candidatureRepo,
        EntretienRepository $entretienRepo,
        CandidatRepository $candidatRepo,
        ChartService $chartService
    ): Response {
        $candidatures = $candidatureRepo->findByOffre($offre->getId());
        $candidatRepo->hydrateNames(array_map(fn($c) => $c->getCandidat(), $candidatures));
        $candidatures = array_values(array_filter($candidatures, fn($c) => trim($c->getCandidat()->getFullName()) !== ''));

        $entretiens = $entretienRepo->findAllWithRelations();
        $entretiens = array_filter($entretiens, fn($e) => $e->getOffre()->getId() === $offre->getId());

        $scoreChartUrl  = !empty($candidatures) ? $chartService->buildScoreDistributionUrl($candidatures) : null;
        $statutChartUrl = !empty($candidatures) ? $chartService->buildStatutChartUrl($candidatures) : null;

        return $this->render('offre/show.html.twig', [
            'offre'          => $offre,
            'questions'      => $questionRepo->findByOffre($offre->getId()),
            'candidatures'   => $candidatures,
            'entretiens'     => $entretiens,
            'scoreChartUrl'  => $scoreChartUrl,
            'statutChartUrl' => $statutChartUrl,
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
        EntityManagerInterface $em
    ): Response {
        $candidature = $repo->find($id);
        if ($candidature) {
            $candidature->setStatutAdmin($statut);
            $candidature->setAdmis($statut === 1);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        }
        return $this->redirectToRoute('admin_candidatures');
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
            return new JsonResponse(['success' => false, 'error' => 'Fichier CV introuvable'], 404);
        }

        try {
            // Extract text from PDF using pdftotext if available, else read raw
            $cvText = '';
            if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'pdf') {
                $cvText = shell_exec('pdftotext ' . escapeshellarg($fullPath) . ' -') ?? '';
            }
            if (empty(trim($cvText))) {
                $cvText = file_get_contents($fullPath);
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
