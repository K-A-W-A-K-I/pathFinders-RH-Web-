<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Entity\Question;
use App\Form\OffreType;
use App\Form\QuestionType;
use App\Repository\CandidatureRepository;
use App\Repository\EntretienRepository;
use App\Repository\OffreRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        EntretienRepository $entretienRepo
    ): Response {
        $candidatures = $candidatureRepo->findByOffre($offre->getId());
        $entretiens   = $entretienRepo->findAllWithRelations();
        $entretiens   = array_filter($entretiens, fn($e) => $e->getOffre()->getId() === $offre->getId());

        return $this->render('offre/show.html.twig', [
            'offre'        => $offre,
            'questions'    => $questionRepo->findByOffre($offre->getId()),
            'candidatures' => $candidatures,
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
}
