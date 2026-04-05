<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Favorite;
use App\Repository\CategorieEvenementRepository;
use App\Repository\EvenementRepository;
use App\Repository\FavoriteRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/evenements', name: 'user_evenement_')]
class UserEventController extends AbstractController
{
    public function __construct(
        private readonly EvenementRepository $evenementRepo,
        private readonly CategorieEvenementRepository $categorieRepo,
        private readonly FavoriteRepository $favoriteRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $categorieValue = $request->query->get('categorie');
        $categorieId = is_numeric($categorieValue) && (int) $categorieValue > 0 ? (int) $categorieValue : null;
        $favoritesOnly = $request->query->getBoolean('favorites');
        $currentUserId = $this->resolveCurrentUserId($request);

        $evenements = $this->evenementRepo->findByFilters(
            $search ?: null,
            null,
            $categorieId,
            null
        );

        $favoriteIds = $currentUserId ? $this->getFavoriteIds($currentUserId) : [];
        $registeredIds = $currentUserId ? $this->getRegisteredIds($currentUserId) : [];

        if ($favoritesOnly) {
            $evenements = array_values(array_filter(
                $evenements,
                fn (Evenement $evenement) => in_array($evenement->getId(), $favoriteIds, true)
            ));
        }

        return $this->render('user_event/index.html.twig', [
            'evenements' => $evenements,
            'categories' => $this->categorieRepo->findAllOrdered(),
            'search' => $search,
            'categorieId' => $categorieId,
            'favoritesOnly' => $favoritesOnly,
            'favoriteIds' => $favoriteIds,
            'favoriteCount' => count($favoriteIds),
            'registeredIds' => $registeredIds,
            'currentUserId' => $currentUserId,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Evenement $evenement, Request $request): Response
    {
        $currentUserId = $this->resolveCurrentUserId($request);
        $favoriteIds = $currentUserId ? $this->getFavoriteIds($currentUserId) : [];
        $registeredIds = $currentUserId ? $this->getRegisteredIds($currentUserId) : [];

        return $this->render('user_event/show.html.twig', [
            'evenement' => $evenement,
            'isFavorite' => in_array($evenement->getId(), $favoriteIds, true),
            'isRegistered' => in_array($evenement->getId(), $registeredIds, true),
            'currentUserId' => $currentUserId,
        ]);
    }

    #[Route('/{id}/favorite', name: 'toggle_favorite', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleFavorite(Evenement $evenement, Request $request): RedirectResponse
    {
        $this->denyInvalidCsrf($request, 'favorite_evenement_' . $evenement->getId());
        $currentUserId = $this->resolveCurrentUserId($request);

        if (!$currentUserId) {
            $this->addFlash('error', 'Aucun utilisateur actif n a ete trouve pour gerer les favoris.');
            return $this->redirectBack($request);
        }

        $favorite = $this->favoriteRepo->findOneForUserAndEvent($currentUserId, $evenement);

        try {
            if ($favorite instanceof Favorite) {
                $this->entityManager->remove($favorite);
                $this->addFlash('success', 'Evenement retire des favoris.');
            } else {
                $favorite = (new Favorite())
                    ->setUserId($currentUserId)
                    ->setEvenement($evenement)
                    ->setDateAjout(new \DateTime());

                $this->entityManager->persist($favorite);
                $this->addFlash('success', 'Evenement ajoute aux favoris.');
            }

            $this->entityManager->flush();
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de mettre a jour les favoris pour le moment.');
        }

        return $this->redirectBack($request);
    }

    #[Route('/{id}/register', name: 'register', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function register(Evenement $evenement, Request $request): RedirectResponse
    {
        $this->denyInvalidCsrf($request, 'register_evenement_' . $evenement->getId());
        $currentUserId = $this->resolveCurrentUserId($request);

        if (!$currentUserId) {
            $this->addFlash('error', 'Aucun utilisateur actif n a ete trouve pour l inscription.');
            return $this->redirectBack($request);
        }

        if ($evenement->isFull()) {
            $this->addFlash('error', 'Cet evenement est complet.');
            return $this->redirectBack($request);
        }

        $alreadyRegistered = (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM inscription_evenement WHERE id_utilisateur = ? AND id_evenement = ?',
            [$currentUserId, $evenement->getId()]
        );

        if ($alreadyRegistered) {
            $this->addFlash('error', 'Vous etes deja inscrit a cet evenement.');
            return $this->redirectBack($request);
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->insert('inscription_evenement', [
                'id_evenement' => $evenement->getId(),
                'id_utilisateur' => $currentUserId,
                'statut_inscription' => 'CONFIRME',
                'statut_paiement' => $evenement->getPrix() && (float) $evenement->getPrix() > 0 ? 'EN_ATTENTE' : 'NON_REQUIS',
            ]);

            $this->connection->executeStatement(
                'UPDATE evenement
                 SET places_reservees = places_reservees + 1,
                     places_restantes = GREATEST(0, places_restantes - 1),
                     statut = CASE WHEN GREATEST(0, places_restantes - 1) = 0 THEN "Complet" ELSE statut END
                 WHERE id_evenement = ?',
                [$evenement->getId()]
            );

            $this->connection->commit();
            $this->addFlash('success', 'Inscription effectuee avec succes.');
        } catch (\Throwable) {
            $this->connection->rollBack();
            $this->addFlash('error', 'Impossible de finaliser l inscription.');
        }

        return $this->redirectBack($request);
    }

    private function getFavoriteIds(int $userId): array
    {
        return $this->favoriteRepo->findEventIdsByUserId($userId);
    }

    private function getRegisteredIds(int $userId): array
    {
        return array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT id_evenement FROM inscription_evenement WHERE id_utilisateur = ?', [$userId])
        );
    }

    private function resolveCurrentUserId(Request $request): ?int
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (is_numeric($sessionUserId)) {
            return (int) $sessionUserId;
        }

        $fallback = $this->connection->fetchOne('SELECT id_utilisateur FROM employee ORDER BY id_utilisateur ASC LIMIT 1');

        return is_numeric($fallback) ? (int) $fallback : null;
    }

    private function denyInvalidCsrf(Request $request, string $tokenId): void
    {
        $token = $request->getPayload()->getString('_token');
        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }
    }

    private function redirectBack(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer');

        return $referer ? new RedirectResponse($referer) : $this->redirectToRoute('user_evenement_index');
    }
}
