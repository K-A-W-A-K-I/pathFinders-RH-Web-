<?php
 
namespace App\Controller\Evenement;
 
use App\Entity\Evenement;
use App\Entity\Favorite;
use App\Repository\CategorieEvenementRepository;
use App\Repository\EvenementRepository;
use App\Repository\FavoriteRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\EventMailer;
 
#[Route('/user/evenements', name: 'user_evenement_')]
class UserEventController extends AbstractController
{
    private const TND_TO_EUR = 0.30;
 
    public function __construct(
        private readonly EvenementRepository $evenementRepo,
        private readonly CategorieEvenementRepository $categorieRepo,
        private readonly FavoriteRepository $favoriteRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly string $stripeSecretKey,
        private readonly EventMailer $eventMailer,
    ) {}
 
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search      = trim((string) $request->query->get('search', ''));
        $categorieValue = $request->query->get('categorie');
        $categorieId = is_numeric($categorieValue) && (int) $categorieValue > 0 ? (int) $categorieValue : null;
        $favoritesOnly  = $request->query->getBoolean('favorites');
        $currentUserId  = $this->getSessionUserId($request);
 
        $evenements = $this->evenementRepo->findByFilters($search ?: null, null, $categorieId, null);
 
        $favoriteIds   = $currentUserId ? $this->getFavoriteIds($currentUserId) : [];
        $registeredIds = $currentUserId ? $this->getRegisteredIds($currentUserId) : [];
 
        if ($favoritesOnly) {
            $evenements = array_values(array_filter(
                $evenements,
                fn(Evenement $e) => in_array($e->getId(), $favoriteIds, true)
            ));
        }
 
        return $this->render('user_event/index.html.twig', [
            'evenements'    => $evenements,
            'categories'    => $this->categorieRepo->findAllOrdered(),
            'search'        => $search,
            'categorieId'   => $categorieId,
            'favoritesOnly' => $favoritesOnly,
            'favoriteIds'   => $favoriteIds,
            'favoriteCount' => count($favoriteIds),
            'registeredIds' => $registeredIds,
            'currentUserId' => $currentUserId,
        ]);
    }
 
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Evenement $evenement, Request $request): Response
    {
        $currentUserId = $this->getSessionUserId($request);
        $favoriteIds   = $currentUserId ? $this->getFavoriteIds($currentUserId) : [];
        $registeredIds = $currentUserId ? $this->getRegisteredIds($currentUserId) : [];
 
        return $this->render('user_event/show.html.twig', [
            'evenement'       => $evenement,
            'isFavorite'      => in_array($evenement->getId(), $favoriteIds, true),
            'isRegistered'    => in_array($evenement->getId(), $registeredIds, true),
            'currentUserId'   => $currentUserId,
            'stripePublicKey' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
            'groqApiKey'      => $_ENV['GROQ_API_KEY'] ?? '',
        ]);
    }
 
    #[Route('/{id}/checkout', name: 'checkout', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function checkout(Evenement $evenement, Request $request): RedirectResponse
    {
        $this->denyInvalidCsrf($request, 'checkout_evenement_' . $evenement->getId());
 
        $currentUserId = $this->getSessionUserId($request);
        if (!$currentUserId) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à un événement.');
            return $this->redirectToRoute('auth_login');
        }
 
        if ($evenement->isFull()) {
            $this->addFlash('error', 'Cet événement est complet.');
            return $this->redirectBack($request);
        }
 
        $alreadyRegistered = (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM inscription_evenement WHERE id_utilisateur = ? AND id_evenement = ?',
            [$currentUserId, $evenement->getId()]
        );
        if ($alreadyRegistered) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectBack($request);
        }
 
        $prixTnd     = (float) $evenement->getPrix();
        $prixEur     = round($prixTnd * self::TND_TO_EUR, 2);
        $amountCents = (int) round($prixEur * 100);
 
        Stripe::setApiKey($this->stripeSecretKey);
 
        $stripeSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $amountCents,
                    'product_data' => [
                        'name'        => $evenement->getTitre(),
                        'description' => sprintf(
                            'Prix original : %.2f TND — Inscription à l\'événement PathFinder',
                            $prixTnd
                        ),
                        'images' => $evenement->getImagePath()
                            ? [$request->getSchemeAndHttpHost() . '/' . ltrim($evenement->getImagePath(), '/')]
                            : [],
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $this->generateUrl(
                'user_evenement_payment_success',
                ['id' => $evenement->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $this->generateUrl(
                'user_evenement_payment_cancel',
                ['id' => $evenement->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'metadata' => [
                'evenement_id' => $evenement->getId(),
                'user_id'      => $currentUserId,
            ],
        ]);
 
        return new RedirectResponse($stripeSession->url, 303);
    }
 
    #[Route('/{id}/payment/success', name: 'payment_success', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function paymentSuccess(Evenement $evenement, Request $request): Response
    {
        $sessionId     = $request->query->get('session_id');
        $currentUserId = $this->getSessionUserId($request);
 
        if (!$sessionId || !$currentUserId) {
            $this->addFlash('error', 'Session de paiement invalide.');
            return $this->redirectToRoute('user_evenement_index');
        }
 
        Stripe::setApiKey($this->stripeSecretKey);
 
        try {
            $stripeSession = StripeSession::retrieve($sessionId);
 
            if (
                $stripeSession->payment_status !== 'paid'
                || (int) $stripeSession->metadata->evenement_id !== $evenement->getId()
                || (int) $stripeSession->metadata->user_id !== $currentUserId
            ) {
                $this->addFlash('error', 'Paiement non confirmé.');
                return $this->redirectToRoute('user_evenement_show', ['id' => $evenement->getId()]);
            }
 
            $alreadyRegistered = (bool) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM inscription_evenement WHERE id_utilisateur = ? AND id_evenement = ?',
                [$currentUserId, $evenement->getId()]
            );
 
            if (!$alreadyRegistered) {
                $this->connection->beginTransaction();
                try {
                    $this->connection->insert('inscription_evenement', [
                        'id_evenement'       => $evenement->getId(),
                        'id_utilisateur'     => $currentUserId,
                        'statut_inscription' => 'CONFIRME',
                        'statut_paiement'    => 'PAYE',
                        'date_paiement'      => (new \DateTime())->format('Y-m-d H:i:s'),
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
                } catch (\Throwable $e) {
                    $this->connection->rollBack();
                    throw $e;
                }
            }
 
            try {
                $inscriptionId = (int) $this->connection->fetchOne(
                    'SELECT id_inscription FROM inscription_evenement WHERE id_utilisateur = ? AND id_evenement = ? ORDER BY id_inscription DESC LIMIT 1',
                    [$currentUserId, $evenement->getId()]
                );
                $user = $this->connection->fetchAssociative(
                    'SELECT nom, prenom, email FROM utilisateurs WHERE id_utilisateur = ?',
                    [$currentUserId]
                );
                if ($user) {
                    $this->eventMailer->sendInscriptionConfirmation(
                        $user['email'],
                        $user['nom'],
                        $user['prenom'],
                        $evenement,
                        'PAYE',
                        $inscriptionId
                    );
                }
            } catch (\Throwable) {}
 
            $this->addFlash('success', 'Paiement confirmé ! Votre inscription est enregistrée. Un email de confirmation vous a été envoyé.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de vérifier le paiement. Contactez le support.');
        }
 
        return $this->redirectToRoute('user_evenement_show', ['id' => $evenement->getId()]);
    }
 
    #[Route('/{id}/payment/cancel', name: 'payment_cancel', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function paymentCancel(Evenement $evenement): Response
    {
        $this->addFlash('error', 'Paiement annulé. Vous pouvez réessayer quand vous voulez.');
        return $this->redirectToRoute('user_evenement_show', ['id' => $evenement->getId()]);
    }
 
    #[Route('/{id}/favorite', name: 'toggle_favorite', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleFavorite(Evenement $evenement, Request $request): RedirectResponse
    {
        $this->denyInvalidCsrf($request, 'favorite_evenement_' . $evenement->getId());
 
        $currentUserId = $this->getSessionUserId($request);
        if (!$currentUserId) {
            $this->addFlash('error', 'Vous devez être connecté pour gérer vos favoris.');
            return $this->redirectToRoute('auth_login');
        }
 
        $favorite = $this->favoriteRepo->findOneForUserAndEvent($currentUserId, $evenement);
 
        try {
            if ($favorite instanceof Favorite) {
                $this->entityManager->remove($favorite);
                $this->addFlash('success', 'Événement retiré des favoris.');
            } else {
                $favorite = (new Favorite())
                    ->setUserId($currentUserId)
                    ->setEvenement($evenement)
                    ->setDateAjout(new \DateTime());
                $this->entityManager->persist($favorite);
                $this->addFlash('success', 'Événement ajouté aux favoris.');
            }
            $this->entityManager->flush();
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de mettre à jour les favoris pour le moment.');
        }
 
        return $this->redirectBack($request);
    }
 
    #[Route('/{id}/register', name: 'register', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function register(Evenement $evenement, Request $request): RedirectResponse
    {
        $this->denyInvalidCsrf($request, 'register_evenement_' . $evenement->getId());
 
        if ($evenement->getPrix() && (float) $evenement->getPrix() > 0) {
            $this->addFlash('error', 'Cet événement est payant. Veuillez passer par le paiement.');
            return $this->redirectToRoute('user_evenement_show', ['id' => $evenement->getId()]);
        }
 
        $currentUserId = $this->getSessionUserId($request);
        if (!$currentUserId) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à un événement.');
            return $this->redirectToRoute('auth_login');
        }
 
        if ($evenement->isFull()) {
            $this->addFlash('error', 'Cet événement est complet.');
            return $this->redirectBack($request);
        }
 
        $alreadyRegistered = (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM inscription_evenement WHERE id_utilisateur = ? AND id_evenement = ?',
            [$currentUserId, $evenement->getId()]
        );
        if ($alreadyRegistered) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectBack($request);
        }
 
        $this->connection->beginTransaction();
        try {
            $this->connection->insert('inscription_evenement', [
                'id_evenement'       => $evenement->getId(),
                'id_utilisateur'     => $currentUserId,
                'statut_inscription' => 'CONFIRME',
                'statut_paiement'    => 'NON_REQUIS',
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
 
            try {
                $inscriptionId = (int) $this->connection->fetchOne(
                    'SELECT id_inscription FROM inscription_evenement WHERE id_utilisateur = ? AND id_evenement = ? ORDER BY id_inscription DESC LIMIT 1',
                    [$currentUserId, $evenement->getId()]
                );
                $user = $this->connection->fetchAssociative(
                    'SELECT nom, prenom, email FROM utilisateurs WHERE id_utilisateur = ?',
                    [$currentUserId]
                );
                if ($user) {
                    $this->eventMailer->sendInscriptionConfirmation(
                        $user['email'],
                        $user['nom'],
                        $user['prenom'],
                        $evenement,
                        'NON_REQUIS',
                        $inscriptionId
                    );
                }
            } catch (\Throwable) {}
 
            $this->addFlash('success', 'Inscription effectuée avec succès. Un email de confirmation vous a été envoyé.');
        } catch (\Throwable) {
            $this->connection->rollBack();
            $this->addFlash('error', "Impossible de finaliser l'inscription.");
        }
 
        return $this->redirectBack($request);
    }
 
    private function getSessionUserId(Request $request): ?int
    {
        $id = $request->getSession()->get('user_id');
        return is_numeric($id) ? (int) $id : null;
    }
 
    private function getFavoriteIds(int $userId): array
    {
        return $this->favoriteRepo->findEventIdsByUserId($userId);
    }
 
    private function getRegisteredIds(int $userId): array
    {
        return array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT id_evenement FROM inscription_evenement WHERE id_utilisateur = ?',
                [$userId]
            )
        );
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