<?php
 
namespace App\Controller\Evenement;
 
use App\Entity\CategorieEvenement;
use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\CategorieEvenementRepository;
use App\Repository\EvenementRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
 
#[Route('/evenements', name: 'evenement_')]
class EvenementController extends AbstractController
{
    public function __construct(
        private readonly EvenementRepository $evenementRepo,
        private readonly CategorieEvenementRepository $categorieRepo,
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
        private readonly Connection $connection,
    ) {}
 
    // ── Événements CRUD ───────────────────────────────────────────────────────
 
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search     = $request->query->get('search', '');
        $statut     = $request->query->get('statut', '');
        $categorieId = (int) $request->query->get('categorie', 0) ?: null;
        $type       = $request->query->get('type', '');
 
        $evenements = $this->evenementRepo->findByFilters(
            $search ?: null,
            $statut ?: null,
            $categorieId,
            $type ?: null
        );
 
        $categories = $this->categorieRepo->findAllOrdered();
        $stats      = $this->evenementRepo->countByStatut();
        $total      = array_sum($stats);
 
        return $this->render('event/index.html.twig', [
            'evenements'  => $evenements,
            'categories'  => $categories,
            'stats'       => $stats,
            'total'       => $total,
            'search'      => $search,
            'statut'      => $statut,
            'categorieId' => $categorieId,
            'type'        => $type,
        ]);
    }
 
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('event/show.html.twig', ['evenement' => $evenement]);
    }
 
    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement, ['is_edit' => false]);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form->get('imageFile')->getData(), $evenement);
 
            if ($userId = $request->getSession()->get('user_id')) {
                $evenement->setCreePar($userId);
            }
 
            $this->em->persist($evenement);
            $this->em->flush();
 
            $this->addFlash('success', 'Événement "' . $evenement->getTitre() . '" créé avec succès.');
            return $this->redirectToRoute('evenement_index');
        }
 
        return $this->render('event/form.html.twig', [
            'form'      => $form,
            'evenement' => $evenement,
            'isEdit'    => false,
            'formTitle' => 'Créer un événement',
        ]);
    }
 
    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Evenement $evenement): Response
    {
        $oldImagePath = $evenement->getImagePath();
        $form = $this->createForm(EvenementType::class, $evenement, ['is_edit' => true]);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form->get('imageFile')->getData(), $evenement, $oldImagePath);
            $this->em->flush();
 
            $this->addFlash('success', 'Événement "' . $evenement->getTitre() . '" mis à jour avec succès.');
            return $this->redirectToRoute('evenement_index');
        }
 
        return $this->render('event/form.html.twig', [
            'form'      => $form,
            'evenement' => $evenement,
            'isEdit'    => true,
            'formTitle' => 'Modifier l\'événement',
        ]);
    }
 
    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Evenement $evenement): Response
    {
        if ($this->isCsrfTokenValid('delete_evenement_' . $evenement->getId(), $request->getPayload()->getString('_token'))) {
            $titre = $evenement->getTitre();
            $this->deleteImageFile($evenement->getImagePath());
            $this->em->remove($evenement);
            $this->em->flush();
            $this->addFlash('success', 'Événement "' . $titre . '" supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression annulée.');
        }
 
        return $this->redirectToRoute('evenement_index');
    }
 
    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function apiStats(): JsonResponse
    {
        return $this->json([
            'stats' => $this->evenementRepo->countByStatut(),
            'total' => $this->evenementRepo->count([]),
        ]);
    }
 
    // ── Catégories ────────────────────────────────────────────────────────────
 
    #[Route('/categories', name: 'categories', methods: ['GET', 'POST'])]
    public function categories(Request $request, ValidatorInterface $validator): Response
    {
        $categories = $this->categorieRepo->findAllOrdered();
        $errors = [];
        $formData = ['nom' => '', 'description' => ''];
 
        if ($request->isMethod('POST') && $request->request->has('_action')) {
            $action = $request->request->get('_action');
 
            // ── Add ──────────────────────────────────────────────────────────
            if ($action === 'add') {
                if (!$this->isCsrfTokenValid('categorie_add', $request->request->get('_token'))) {
                    $this->addFlash('error', 'Token CSRF invalide.');
                    return $this->redirectToRoute('evenement_categories');
                }
 
                $nom = trim((string) $request->request->get('nom', ''));
                $description = trim((string) $request->request->get('description', ''));
 
                $nomErrors = $validator->validate($nom, [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser 100 caractères.'),
                ]);
 
                if (count($nomErrors) > 0) {
                    foreach ($nomErrors as $e) {
                        $errors['nom'][] = $e->getMessage();
                    }
                    $formData = ['nom' => $nom, 'description' => $description];
                } else {
                    $cat = (new CategorieEvenement())
                        ->setNomCategorie($nom)
                        ->setDescription($description ?: null);
                    $this->em->persist($cat);
                    $this->em->flush();
                    $this->addFlash('success', 'Catégorie "' . $nom . '" créée avec succès.');
                    return $this->redirectToRoute('evenement_categories');
                }
            }
 
            // ── Delete ───────────────────────────────────────────────────────
            if ($action === 'delete') {
                $id = (int) $request->request->get('id');
                if (!$this->isCsrfTokenValid('categorie_delete_' . $id, $request->request->get('_token'))) {
                    $this->addFlash('error', 'Token CSRF invalide.');
                    return $this->redirectToRoute('evenement_categories');
                }
                $cat = $this->categorieRepo->find($id);
                if ($cat) {
                    $this->em->remove($cat);
                    $this->em->flush();
                    $this->addFlash('success', 'Catégorie supprimée.');
                }
                return $this->redirectToRoute('evenement_categories');
            }
        }
 
        return $this->render('event/categories.html.twig', [
            'categories' => $categories,
            'errors'     => $errors,
            'formData'   => $formData,
        ]);
    }
 
    // ── Inscriptions list ─────────────────────────────────────────────────────
 
    #[Route('/inscriptions', name: 'inscriptions', methods: ['GET'])]
    public function inscriptions(Request $request): Response
    {
        $search    = trim((string) $request->query->get('search', ''));
        $statut    = $request->query->get('statut', '');
        $eventId   = (int) $request->query->get('event', 0) ?: null;
 
        $qb = $this->connection->createQueryBuilder()
            ->select(
                'ie.id_inscription',
                'ie.statut_inscription',
                'ie.statut_paiement',
                'ie.date_inscription',
                'ie.date_paiement',
                'u.id_utilisateur',
                'u.nom',
                'u.prenom',
                'u.email',
                'e.id_evenement',
                'e.titre AS titre_evenement',
                'e.date_debut',
                'e.date_fin',
                'e.prix'
            )
            ->from('inscription_evenement', 'ie')
            ->join('ie', 'utilisateurs', 'u', 'u.id_utilisateur = ie.id_utilisateur')
            ->join('ie', 'evenement', 'e', 'e.id_evenement = ie.id_evenement')
            ->orderBy('ie.date_inscription', 'DESC');
 
        if ($search) {
            $qb->andWhere('(u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s OR e.titre LIKE :s)')
               ->setParameter('s', '%' . $search . '%');
        }
        if ($statut) {
            $qb->andWhere('ie.statut_inscription = :statut')->setParameter('statut', $statut);
        }
        if ($eventId) {
            $qb->andWhere('ie.id_evenement = :eid')->setParameter('eid', $eventId);
        }
 
        $inscriptions = $qb->fetchAllAssociative();
        $evenements   = $this->evenementRepo->findAll();
 
        return $this->render('event/inscriptions.html.twig', [
            'inscriptions' => $inscriptions,
            'evenements'   => $evenements,
            'search'       => $search,
            'statut'       => $statut,
            'eventId'      => $eventId,
        ]);
    }
 
    // ── Private helpers ───────────────────────────────────────────────────────
 
    private function handleImageUpload(?UploadedFile $imageFile, Evenement $evenement, ?string $oldImagePath = null): void
    {
        if (!$imageFile instanceof UploadedFile) {
            return;
        }
 
        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
 
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower()->toString();
        if ($safeFilename === '') {
            $safeFilename = 'image-evenement';
        }
 
        $extension   = $imageFile->guessExtension() ?: 'bin';
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;
 
        try {
            $imageFile->move($uploadDirectory, $newFilename);
            $evenement->setImagePath('uploads/events/' . $newFilename);
 
            if ($oldImagePath !== null && $oldImagePath !== '') {
                $this->deleteImageFile($oldImagePath);
            }
        } catch (FileException) {
            throw new \RuntimeException("Impossible d'enregistrer l'image téléchargée.");
        }
    }
 
    private function deleteImageFile(?string $imagePath): void
    {
        if ($imagePath === null || $imagePath === '') {
            return;
        }
        $absolutePath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($imagePath, '/');
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }
}