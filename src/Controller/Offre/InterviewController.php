<?php

namespace App\Controller\Offre;

use App\Repository\EntretienRepository;
use App\Service\AiService;
use App\Service\CandidatureMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[Route('/entretien/interview')]
class InterviewController extends AbstractController
{
    /**
     * The candidate opens this page via the link in their confirmation email.
     * The token identifies the entretien uniquely.
     */
    #[Route('/{token}', name: 'interview_chat')]
    public function chat(
        string $token,
        EntretienRepository $repo,
        #[Autowire('%kernel.environment%')] string $env
    ): Response {
        $entretien = $repo->findOneBy(['interviewToken' => $token]);

        if (!$entretien) {
            throw $this->createNotFoundException('Lien d\'entretien invalide ou expiré.');
        }

        if ($entretien->getStatut() !== 'CONFIRME') {
            return $this->render('interview/not_ready.html.twig', [
                'entretien' => $entretien,
            ]);
        }

        // In dev mode, skip the time-gate so you can test anytime
        if ($env !== 'dev') {
            $now      = new \DateTime();
            $date     = $entretien->getDateEntretien();
            $openFrom = (clone $date)->modify('-15 minutes');

            if ($now < $openFrom) {
                return $this->render('interview/too_early.html.twig', [
                    'entretien' => $entretien,
                    'openFrom'  => $openFrom,
                ]);
            }
        }

        if ($entretien->isInterviewCompleted()) {
            return $this->render('interview/already_done.html.twig', [
                'entretien' => $entretien,
            ]);
        }

        return $this->render('interview/chat.html.twig', [
            'entretien' => $entretien,
            'token'     => $token,
        ]);
    }

    /**
     * AJAX endpoint — receives the conversation history and returns the next AI message.
     * Body: { messages: [{role, content}, ...] }
     */
    #[Route('/{token}/message', name: 'interview_message', methods: ['POST'])]
    public function message(
        string $token,
        Request $request,
        EntretienRepository $repo,
        AiService $ai,
        EntityManagerInterface $em
    ): JsonResponse {
        $entretien = $repo->findOneBy(['interviewToken' => $token]);

        if (!$entretien || $entretien->getStatut() !== 'CONFIRME') {
            return new JsonResponse(['error' => 'Entretien invalide.'], 403);
        }

        if ($entretien->isInterviewCompleted()) {
            return new JsonResponse(['error' => 'Entretien déjà terminé.'], 400);
        }

        $body     = json_decode($request->getContent(), true);
        $messages = $body['messages'] ?? [];

        if (!is_array($messages)) {
            return new JsonResponse(['error' => 'Format invalide.'], 400);
        }

        try {
            $offre  = $entretien->getOffre();
            $result = $ai->conductInterview(
                $offre->getTitre(),
                $offre->getDescription(),
                $offre->getDomaine(),
                $messages,
                5
            );

            if ($result['type'] === 'result') {
                $entretien->setInterviewScore($result['score']);
                $entretien->setInterviewCompleted(true);
                // Preserve any existing admin note, append AI summary below it
                $existing = $entretien->getNotes();
                $entretien->setNotes(
                    $existing
                        ? $existing . ' — ' . $result['summary']
                        : $result['summary']
                );
                $em->flush();
            }

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin view: see interview results for a specific entretien.
     */
    #[Route('/admin/result/{id}', name: 'interview_admin_result')]
    public function adminResult(int $id, EntretienRepository $repo): Response
    {
        $entretien = $repo->find($id);
        if (!$entretien) {
            throw $this->createNotFoundException();
        }

        return $this->render('interview/admin_result.html.twig', [
            'entretien' => $entretien,
        ]);
    }
}
