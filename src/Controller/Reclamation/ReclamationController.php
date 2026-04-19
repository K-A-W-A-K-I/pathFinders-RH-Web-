<?php

namespace App\Controller\Reclamation;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use App\Service\Reclamation\GeocodingService;
use App\Service\Reclamation\ReclamationLetterGeneratorService;
use App\Service\Reclamation\ReclamationModerationService;
use App\Service\Security\RecaptchaVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reclamations', name: 'reclamation_')]
class ReclamationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ReclamationRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('auth_login');

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $repo->findByUser($user->getId()),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        RecaptchaVerifier $recaptchaVerifier,
        ReclamationModerationService $moderationService,
        GeocodingService $geocodingService,
        ReclamationLetterGeneratorService $letterGeneratorService,
    ): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('auth_login');

        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $errors = $this->validate($data);

            if (!$this->isCsrfTokenValid('reclamation_new', (string) $request->request->get('_token'))) {
                $errors['global'] = 'Session expirée. Veuillez réessayer.';
            }

            $captchaToken = (string) $request->request->get('g-recaptcha-response', '');
            if (!$recaptchaVerifier->isEnabled()) {
                $errors['recaptcha'] = 'La protection reCAPTCHA est obligatoire et doit être activée.';
            } elseif (!$recaptchaVerifier->verify($captchaToken, $request->getClientIp())) {
                $errors['recaptcha'] = 'Validation reCAPTCHA invalide. Veuillez réessayer.';
            }

            if (empty($errors)) {
                try {
                    $moderation = $moderationService->moderate(
                        (int) $user->getId(),
                        trim((string) ($data['titre'] ?? '')),
                        trim((string) ($data['description'] ?? '')),
                    );

                    if ($moderation['status'] === 'rejected') {
                        $errors['global'] = 'Votre réclamation ne peut pas être envoyée en l\'état. Merci de reformuler le texte de manière claire et respectueuse.';
                    }

                    $data['_moderation_decision'] = $moderation['status'];
                    $data['_moderation_reason'] = $moderation['reason'];
                    $data['_moderation_score'] = $moderation['score'];
                    $data['_moderation_flags'] = $moderation['flags'];
                    $data['_moderation_summary'] = $moderation['summary'];
                    $data['_moderation_urgency'] = $moderation['urgency'];
                    $data['_moderation_sentiment'] = $moderation['sentiment'];
                } catch (\Throwable) {
                    $errors['global'] = 'Le service de modération est momentanément indisponible. Veuillez réessayer.';
                }
            }

            if (empty($errors)) {
                $r = new Reclamation();
                $dateIncident = $this->parseDateIncident((string) ($data['date_incident'] ?? ''));
                $lieu = trim((string) ($data['lieu'] ?? ''));
                $latitude = trim((string) ($data['latitude'] ?? ''));
                $longitude = trim((string) ($data['longitude'] ?? ''));

                if (($latitude === '' || $longitude === '') && $lieu !== '' && $geocodingService->isEnabled()) {
                    try {
                        $geo = $geocodingService->geocode($lieu);
                        if ($geo !== null) {
                            $latitude = (string) $geo['latitude'];
                            $longitude = (string) $geo['longitude'];
                            $lieu = (string) $geo['display_name'];
                        }
                    } catch (\Throwable) {
                        // Geocoding fallback should not block a valid reclamation.
                    }
                }

                $r->setIdUtilisateur($user->getId());
                $r->setTitre(trim((string) $data['titre']));
                $r->setDescription(trim((string) $data['description']));
                $r->setDateIncident($dateIncident);
                $r->setLieu($lieu !== '' ? $lieu : null);
                $r->setLatitude($latitude !== '' ? $latitude : null);
                $r->setLongitude($longitude !== '' ? $longitude : null);
                $decision = (string) ($data['_moderation_decision'] ?? 'accepted');
                $r->setModerationDecision($decision);
                $r->setModerationReason((string) ($data['_moderation_reason'] ?? 'Contenu accepté par modération automatique.'));
                $r->setModerationScore((float) ($data['_moderation_score'] ?? 0.0));
                $r->setModerationFlags(is_array($data['_moderation_flags'] ?? null) ? $data['_moderation_flags'] : []);
                $r->setModerationSummary((string) ($data['_moderation_summary'] ?? ''));
                $r->setModerationUrgency((string) ($data['_moderation_urgency'] ?? 'medium'));
                $r->setModerationSentiment((string) ($data['_moderation_sentiment'] ?? 'neutral'));
                $r->setManualReviewRequired($decision === 'review');
                $em->persist($r);
                $em->flush();

                if ($decision === 'review') {
                    $this->addFlash('warning', 'Réclamation envoyée et marquée pour vérification manuelle.');
                } else {
                    $this->addFlash('success', 'Réclamation envoyée avec succès.');
                }
                return $this->redirectToRoute('reclamation_index');
            }
        }

        // Detect which base to use
        $role = method_exists($user, 'getRole') ? $user->getRole() : '';
        $base = in_array($role, ['employe', 'ROLE_WORKER']) ? 'worker' : 'client';

        return $this->render('reclamation/form.html.twig', [
            'errors' => $errors,
            'data'   => $data,
            'base'   => $base,
            'recaptcha_site_key' => $this->getParameter('app.recaptcha.site_key'),
            'google_maps_api_key' => $this->getParameter('app.google_maps.api_key'),
            'ai_letter_enabled' => $letterGeneratorService->isEnabled(),
        ]);
    }

    #[Route('/ai/lettre', name: 'ai_letter', methods: ['POST'])]
    public function generateLetter(
        Request $request,
        ReclamationLetterGeneratorService $letterGeneratorService,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['ok' => false, 'message' => 'Utilisateur non authentifié.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$this->isCsrfTokenValid('reclamation_new', (string) $request->request->get('_token'))) {
            return $this->json(['ok' => false, 'message' => 'Session expirée. Veuillez recharger la page.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $letter = $letterGeneratorService->generate([
                'titre' => (string) $request->request->get('titre', ''),
                'description' => (string) $request->request->get('description', ''),
                'date_incident' => (string) $request->request->get('date_incident', ''),
                'lieu' => (string) $request->request->get('lieu', ''),
                'details' => (string) $request->request->get('details', ''),
            ]);

            return $this->json(['ok' => true, 'letter' => $letter]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return $this->json(['ok' => false, 'message' => 'La génération IA est indisponible pour le moment.'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route('/geo/search', name: 'geo_search', methods: ['GET'])]
    public function geocodeSearch(Request $request, GeocodingService $geocodingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['ok' => false, 'message' => 'Utilisateur non authentifié.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $query = trim((string) $request->query->get('q', ''));
        if ($query === '') {
            return $this->json(['ok' => false, 'message' => 'Adresse vide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $geo = $geocodingService->geocode($query);
            if ($geo === null) {
                return $this->json(['ok' => false, 'message' => 'Aucune correspondance trouvée.'], JsonResponse::HTTP_NOT_FOUND);
            }

            return $this->json(['ok' => true, 'data' => $geo]);
        } catch (\Throwable) {
            return $this->json(['ok' => false, 'message' => 'Service de geolocalisation indisponible.'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, ReclamationRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $r    = $repo->find($id);

        if ($r && $user && $r->getIdUtilisateur() === $user->getId()) {
            $em->remove($r);
            $em->flush();
            $this->addFlash('success', 'Réclamation supprimée.');
        }

        return $this->redirectToRoute('reclamation_index');
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['titre'] ?? ''))) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen(trim($data['titre'])) < 5) {
            $errors['titre'] = 'Le titre doit contenir au moins 5 caractères.';
        } elseif (strlen(trim($data['titre'])) > 255) {
            $errors['titre'] = 'Le titre ne peut pas dépasser 255 caractères.';
        }
        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est obligatoire.';
        } elseif (strlen(trim($data['description'])) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères.';
        }

        $lieu = trim((string) ($data['lieu'] ?? ''));
        if ($lieu !== '' && strlen($lieu) > 255) {
            $errors['lieu'] = 'Le lieu ne peut pas dépasser 255 caractères.';
        }

        $dateIncidentRaw = trim((string) ($data['date_incident'] ?? ''));
        if ($dateIncidentRaw !== '' && $this->parseDateIncident($dateIncidentRaw) === null) {
            $errors['date_incident'] = 'La date de l\'incident est invalide.';
        }

        $latitudeRaw = trim((string) ($data['latitude'] ?? ''));
        $longitudeRaw = trim((string) ($data['longitude'] ?? ''));
        if ($latitudeRaw !== '' && !is_numeric($latitudeRaw)) {
            $errors['latitude'] = 'Latitude invalide.';
        }
        if ($longitudeRaw !== '' && !is_numeric($longitudeRaw)) {
            $errors['longitude'] = 'Longitude invalide.';
        }
        if ($latitudeRaw !== '' && is_numeric($latitudeRaw)) {
            $latitude = (float) $latitudeRaw;
            if ($latitude < -90 || $latitude > 90) {
                $errors['latitude'] = 'Latitude hors limites (-90 à 90).';
            }
        }
        if ($longitudeRaw !== '' && is_numeric($longitudeRaw)) {
            $longitude = (float) $longitudeRaw;
            if ($longitude < -180 || $longitude > 180) {
                $errors['longitude'] = 'Longitude hors limites (-180 à 180).';
            }
        }

        return $errors;
    }

    private function parseDateIncident(string $dateRaw): ?\DateTimeInterface
    {
        $dateRaw = trim($dateRaw);
        if ($dateRaw === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
