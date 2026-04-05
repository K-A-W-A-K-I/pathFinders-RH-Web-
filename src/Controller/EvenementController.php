<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\CategorieEvenementRepository;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/evenements', name: 'evenement_')]
class EvenementController extends AbstractController
{
    public function __construct(
        private readonly EvenementRepository $evenementRepo,
        private readonly CategorieEvenementRepository $categorieRepo,
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', '');
        $categorieId = (int) $request->query->get('categorie', 0) ?: null;
        $type = $request->query->get('type', '');

        $evenements = $this->evenementRepo->findByFilters(
            $search ?: null,
            $statut ?: null,
            $categorieId,
            $type ?: null
        );

        $categories = $this->categorieRepo->findAllOrdered();
        $stats = $this->evenementRepo->countByStatut();
        $total = array_sum($stats);

        return $this->render('event/index.html.twig', [
            'evenements' => $evenements,
            'categories' => $categories,
            'stats' => $stats,
            'total' => $total,
            'search' => $search,
            'statut' => $statut,
            'categorieId' => $categorieId,
            'type' => $type,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('event/show.html.twig', [
            'evenement' => $evenement,
        ]);
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

            $this->addFlash('success', 'Evenement "' . $evenement->getTitre() . '" cree avec succes.');

            return $this->redirectToRoute('evenement_index');
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
            'isEdit' => false,
            'formTitle' => 'Creer un evenement',
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

            $this->addFlash('success', 'Evenement "' . $evenement->getTitre() . '" mis a jour avec succes.');

            return $this->redirectToRoute('evenement_index');
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
            'isEdit' => true,
            'formTitle' => 'Modifier l evenement',
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
            $this->addFlash('success', 'Evenement "' . $titre . '" supprime avec succes.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression annulee.');
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

        $extension = $imageFile->guessExtension() ?: 'bin';
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;

        try {
            $imageFile->move($uploadDirectory, $newFilename);
            $evenement->setImagePath('uploads/events/' . $newFilename);

            if ($oldImagePath !== null && $oldImagePath !== '') {
                $this->deleteImageFile($oldImagePath);
            }
        } catch (FileException) {
            throw new \RuntimeException('Impossible d enregistrer l image telechargee.');
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
