<?php

namespace App\Controller\Reclamation;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('auth_login');

        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $errors = $this->validate($data);

            if (empty($errors)) {
                $r = new Reclamation();
                $r->setIdUtilisateur($user->getId());
                $r->setTitre(trim($data['titre']));
                $r->setDescription(trim($data['description']));
                $em->persist($r);
                $em->flush();

                $this->addFlash('success', 'Réclamation envoyée avec succès.');
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
        ]);
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
        return $errors;
    }
}
