<?php

namespace App\Controller\Reclamation;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Analytics\DashboardAnalyticsCoordinator;
use App\Service\Mailer\BrevoMailer;
use App\Service\Reclamation\ReclamationReplyGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reclamations', name: 'admin_reclamation_')]
class AdminReclamationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        ReclamationRepository $repo,
        UtilisateurRepository $userRepo,
        PaginatorInterface $paginator,
        DashboardAnalyticsCoordinator $analyticsCoordinator,
    ): Response
    {
        $qb = $repo->createQueryBuilder('r')->orderBy('r.dateCreation', 'DESC');
        $reclamations = $paginator->paginate(
            $qb,
            max(1, $request->query->getInt('page', 1)),
            10
        );

        // Attach user info
        $users = [];
        $reclamationsPage = [];
        foreach ($reclamations as $r) {
            $reclamationsPage[] = $r;
            $users[$r->getIdUtilisateur()] ??= $userRepo->find($r->getIdUtilisateur());
        }

        $analyticsCoordinator->refreshReclamationAnalytics($reclamationsPage);

        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'users'        => $users,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, ReclamationRepository $repo, UtilisateurRepository $userRepo): Response
    {
        $reclamation = $repo->find($id);
        if (!$reclamation instanceof Reclamation) {
            throw $this->createNotFoundException();
        }

        $user = $userRepo->find($reclamation->getIdUtilisateur());

        return $this->render('admin/reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/statut', name: 'statut', methods: ['POST'])]
    public function updateStatut(int $id, Request $request, ReclamationRepository $repo, EntityManagerInterface $em): Response
    {
        $r = $repo->find($id);
        if ($r) {
            $statut = $request->request->get('statut');
            $allowed = ['En attente', 'En cours', 'Résolu', 'Répondu', 'Fermé'];
            if (in_array($statut, $allowed)) {
                $r->setStatut($statut);
                $em->flush();
                $this->addFlash('success', 'Statut mis à jour.');
            }
        }

        if ((string) $request->request->get('redirect') === 'show') {
            return $this->redirectToRoute('admin_reclamation_show', ['id' => $id]);
        }

        return $this->redirectToRoute('admin_reclamation_index');
    }

    #[Route('/{id}/ai-reply', name: 'ai_reply', methods: ['POST'])]
    public function generateAiReply(
        int $id,
        Request $request,
        ReclamationRepository $repo,
        ReclamationReplyGeneratorService $replyGenerator,
    ): JsonResponse {
        $reclamation = $repo->find($id);
        if (!$reclamation instanceof Reclamation) {
            return $this->json(['ok' => false, 'message' => 'Réclamation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('admin_reclamation_reply_' . $reclamation->getId(), (string) $request->request->get('_token'))) {
            return $this->json(['ok' => false, 'message' => 'Session expirée.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $reply = $replyGenerator->generate($reclamation);

            return $this->json(['ok' => true, 'reply' => $reply]);
        } catch (\Throwable) {
            return $this->json(['ok' => false, 'message' => 'La génération IA est indisponible pour le moment.'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route('/{id}/reply', name: 'reply_send', methods: ['POST'])]
    public function sendReply(
        int $id,
        Request $request,
        ReclamationRepository $repo,
        UtilisateurRepository $userRepo,
        EntityManagerInterface $em,
        BrevoMailer $brevoMailer,
    ): Response {
        $reclamation = $repo->find($id);
        if (!$reclamation instanceof Reclamation) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('admin_reclamation_reply_' . $reclamation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée. Veuillez réessayer.');
            return $this->redirectToRoute('admin_reclamation_show', ['id' => $id]);
        }

        $reply = trim((string) $request->request->get('admin_reply', ''));
        if ($reply === '' || mb_strlen($reply) < 10) {
            $this->addFlash('error', 'La réponse doit contenir au moins 10 caractères.');
            return $this->redirectToRoute('admin_reclamation_show', ['id' => $id]);
        }

        $candidate = $userRepo->find($reclamation->getIdUtilisateur());

        $reclamation->setAdminReply($reply);
        $reclamation->setAdminReplySentAt(new \DateTimeImmutable());
        $reclamation->setStatut('Répondu');

        $emailSent = false;
        $emailError = null;
        if ($candidate && method_exists($candidate, 'getEmail') && (string) $candidate->getEmail() !== '') {
            try {
                $emailSent = $brevoMailer->sendReclamationReply(
                    (string) $candidate->getEmail(),
                    method_exists($candidate, 'getFullName') ? (string) $candidate->getFullName() : '',
                    $reclamation->getTitre(),
                    $reply,
                );
            } catch (\Throwable $exception) {
                $emailSent = false;
                $emailError = $exception->getMessage();
            }
        } else {
            $emailError = 'Email candidat introuvable.';
        }

        $reclamation->setAdminReplyEmailStatus($emailSent ? 'sent' : 'failed');
        $reclamation->setAdminReplyEmailError($emailSent ? null : ($emailError ?? 'Envoi Brevo échoué.'));

        $em->flush();

        if ($emailSent) {
            $this->addFlash('success', 'Réponse enregistrée et email envoyé au candidat.');
        } else {
            $this->addFlash('warning', 'Réponse enregistrée, mais l\'email n\'a pas pu être envoyé.');
        }

        return $this->redirectToRoute('admin_reclamation_show', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, ReclamationRepository $repo, EntityManagerInterface $em): Response
    {
        $r = $repo->find($id);
        if ($r) {
            $em->remove($r);
            $em->flush();
            $this->addFlash('success', 'Réclamation supprimée.');
        }
        return $this->redirectToRoute('admin_reclamation_index');
    }
}
