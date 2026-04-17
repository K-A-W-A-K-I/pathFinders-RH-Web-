<?php

namespace App\Controller\Offre;

use App\Entity\Entretien;
use App\Repository\CandidatRepository;
use App\Repository\CandidatureRepository;
use App\Repository\EntretienRepository;
use App\Repository\OffreRepository;
use App\Service\CandidatureMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class EntretienController extends AbstractController
{
    // ── Candidat: planifier un entretien ──────────────────────────────────

    #[Route('/entretien/planifier/{candidatureId}', name: 'entretien_planifier')]
    public function planifier(
        int $candidatureId,
        CandidatureRepository $candidatureRepo,
        EntretienRepository $entretienRepo
    ): Response {
        $candidature = $candidatureRepo->find($candidatureId);
        if (!$candidature) {
            throw $this->createNotFoundException();
        }

        $bookedSlots = $entretienRepo->getBookedSlots();
        $bookedTs    = array_map(fn($d) => $d->getTimestamp(), $bookedSlots);

        return $this->render('entretien/planifier.html.twig', [
            'candidature' => $candidature,
            'bookedTs'    => $bookedTs,
        ]);
    }

    #[Route('/entretien/confirmer', name: 'entretien_confirmer', methods: ['POST'])]
    public function confirmer(
        Request $request,
        CandidatureRepository $candidatureRepo,
        CandidatRepository $candidatRepo,
        EntretienRepository $entretienRepo,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        $candidatureId = (int) $request->request->get('candidature_id');
        $dateStr       = $request->request->get('date_entretien');

        $candidature = $candidatureRepo->find($candidatureId);
        if (!$candidature || !$dateStr) {
            $this->addFlash('error', 'Données invalides.');
            return $this->redirectToRoute('candidature_mes');
        }

        $date = new \DateTime($dateStr);

        if ($entretienRepo->hasConflict($date)) {
            $this->addFlash('error', 'Ce créneau est déjà réservé. Choisissez un autre horaire.');
            return $this->redirectToRoute('entretien_planifier', ['candidatureId' => $candidatureId]);
        }

        $userId   = $session->get('user_id', 1);
        $candidat = $candidatRepo->findByUserId($userId);

        $entretien = new Entretien();
        $entretien->setCandidature($candidature);
        $entretien->setOffre($candidature->getOffre());
        $entretien->setCandidat($candidat);
        $entretien->setDateEntretien($date);

        $em->persist($entretien);
        $em->flush();

        $this->addFlash('success', 'Entretien planifié pour le ' . $date->format('d/m/Y H:i') . '.');
        return $this->redirectToRoute('candidature_mes');
    }

    // ── Admin: gestion des entretiens ─────────────────────────────────────

    #[Route('/admin/entretiens', name: 'entretien_admin')]
    public function adminIndex(Request $request, EntretienRepository $repo, \App\Repository\CandidatRepository $candidatRepo): Response
    {
        $filter     = $request->query->get('statut', 'Tous');
        $entretiens = $repo->findAllWithRelations($filter);

        $candidats = array_map(fn($e) => $e->getCandidat(), $entretiens);
        $candidatRepo->hydrateNames($candidats);

        $entretiens = array_values(array_filter($entretiens, fn($e) => trim($e->getCandidat()->getFullName()) !== ''));

        return $this->render('entretien/admin.html.twig', [
            'entretiens' => $entretiens,
            'filter'     => $filter,
        ]);
    }

    #[Route('/admin/entretiens/{id}/statut', name: 'entretien_update_statut', methods: ['POST'])]
    public function updateStatut(
        Entretien $entretien,
        Request $request,
        EntretienRepository $repo,
        CandidatRepository $candidatRepo,
        EntityManagerInterface $em,
        CandidatureMailer $mailer
    ): Response {
        $statut = $request->request->get('statut');
        $notes  = $request->request->get('notes', '');

        if ($statut === Entretien::STATUT_CONFIRME && $repo->hasConflict($entretien->getDateEntretien(), $entretien->getId())) {
            $this->addFlash('error', 'Conflit de créneau détecté. Refusez l\'autre entretien d\'abord.');
            return $this->redirectToRoute('entretien_admin');
        }

        $entretien->setStatut($statut);
        // Only overwrite notes if the admin actually typed something
        if ($notes) {
            $entretien->setNotes($notes);
        }

        // Generate interview token when confirming
        if ($statut === Entretien::STATUT_CONFIRME && !$entretien->getInterviewToken()) {
            $entretien->setInterviewToken(bin2hex(random_bytes(32)));
        }

        $em->flush();

        // Envoyer email de confirmation si entretien confirmé
        if ($statut === Entretien::STATUT_CONFIRME) {
            try {
                $candidat = $entretien->getCandidat();
                $candidatRepo->hydrateNames([$candidat]);
                $email = $candidat->getEmail();
                $nom   = $candidat->getFullName() ?: 'Candidat';
                if ($email) {
                    $mailer->sendEntretienConfirme($entretien, $email, $nom);
                }
            } catch (\Throwable) {
                // Ne pas bloquer si l'email échoue
            }
        }

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('entretien_admin');
    }

    #[Route('/admin/entretiens/slots', name: 'entretien_slots_json')]
    public function slotsJson(EntretienRepository $repo): JsonResponse
    {
        $slots = $repo->getBookedSlots();
        return new JsonResponse(array_map(fn($d) => $d->getTimestamp() * 1000, $slots));
    }
}
