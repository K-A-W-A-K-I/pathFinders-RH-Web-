<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reclamations', name: 'admin_reclamation_')]
class AdminReclamationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ReclamationRepository $repo, UtilisateurRepository $userRepo): Response
    {
        $reclamations = $repo->findBy([], ['dateCreation' => 'DESC']);

        // Attach user info
        $users = [];
        foreach ($reclamations as $r) {
            $users[$r->getIdUtilisateur()] ??= $userRepo->find($r->getIdUtilisateur());
        }

        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'users'        => $users,
        ]);
    }

    #[Route('/{id}/statut', name: 'statut', methods: ['POST'])]
    public function updateStatut(int $id, Request $request, ReclamationRepository $repo, EntityManagerInterface $em): Response
    {
        $r = $repo->find($id);
        if ($r) {
            $statut = $request->request->get('statut');
            $allowed = ['En attente', 'En cours', 'Résolu', 'Fermé'];
            if (in_array($statut, $allowed)) {
                $r->setStatut($statut);
                $em->flush();
                $this->addFlash('success', 'Statut mis à jour.');
            }
        }
        return $this->redirectToRoute('admin_reclamation_index');
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
