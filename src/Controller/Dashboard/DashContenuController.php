<?php

namespace App\Controller\Dashboard;

use App\Entity\ContenuModule;
use App\Form\ContenuModuleType;
use App\Repository\ContenuModuleRepository;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/contenus', name: 'dash_contenu_')]
class DashContenuController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(FormationRepository $formRepo): Response
    {
        return $this->render('dashboard/contenu/index.html.twig', [
            'formations' => $formRepo->findAll(),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/new/{formationId}', name: 'new_for_formation')]
    public function new(Request $request, EntityManagerInterface $em, FormationRepository $formRepo, ?int $formationId = null): Response
    {
        $contenu = new ContenuModule();

        if ($formationId) {
            $formation = $formRepo->find($formationId);
            if ($formation) $contenu->setFormation($formation);
        }

        $form = $this->createForm(ContenuModuleType::class, $contenu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contenu);
            $em->flush();
            $this->addFlash('success', 'Module créé avec succès.');
            
            // Rediriger vers la liste des modules de la formation
            $formationId = $contenu->getFormation()->getIdFormation();
            return $this->redirectToRoute('dash_contenu_by_formation', ['formationId' => $formationId]);
        }

        return $this->render('dashboard/contenu/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'Nouveau module',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request, ContenuModuleRepository $repo, EntityManagerInterface $em): Response
    {
        $contenu = $repo->find($id);
        if (!$contenu) throw $this->createNotFoundException();

        $form = $this->createForm(ContenuModuleType::class, $contenu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Module modifié avec succès.');
            
            // Rediriger vers la liste des modules de la formation
            $formationId = $contenu->getFormation()->getIdFormation();
            return $this->redirectToRoute('dash_contenu_by_formation', ['formationId' => $formationId]);
        }

        return $this->render('dashboard/contenu/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'Modifier le module',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, ContenuModuleRepository $repo, EntityManagerInterface $em): Response
    {
        $contenu = $repo->find($id);
        if (!$contenu) throw $this->createNotFoundException();

        $formationId = $contenu->getFormation()->getIdFormation();

        if ($this->isCsrfTokenValid('delete_contenu_' . $id, $request->request->get('_token'))) {
            $em->remove($contenu);
            $em->flush();
            $this->addFlash('success', 'Module supprimé.');
        }

        // Rediriger vers la liste des modules de la formation
        return $this->redirectToRoute('dash_contenu_by_formation', ['formationId' => $formationId]);
    }

    #[Route('/formation/{formationId}', name: 'by_formation')]
    public function byFormation(int $formationId, FormationRepository $formationRepo, ContenuModuleRepository $repo): Response
    {
        $formation = $formationRepo->find($formationId);
        if (!$formation) throw $this->createNotFoundException('Formation introuvable.');

        $modules = $repo->findBy(['formation' => $formation], ['ordre' => 'ASC']);

        return $this->render('dashboard/contenu/index.html.twig', [
            'formation' => $formation,
            'modules'   => $modules,
        ]);
    }
}
