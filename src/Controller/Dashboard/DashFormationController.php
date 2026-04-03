<?php

namespace App\Controller\Dashboard;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/formations', name: 'dash_formation_')]
class DashFormationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, FormationRepository $repo, CategorieFormationRepository $catRepo): Response
    {
        $q      = $request->query->get('q', '');
        $sort   = $request->query->get('sort', 'titre');
        $dir    = $request->query->get('dir', 'ASC');
        $catId  = $request->query->get('categorie') ? (int)$request->query->get('categorie') : null;

        $formations = $q
            ? $repo->search($q, $catId)
            : $repo->findByCategorieAndSort($catId, $sort, $dir);

        return $this->render('dashboard/formation/index.html.twig', [
            'formations'  => $formations,
            'categories'  => $catRepo->findAll(),
            'q'           => $q,
            'sort'        => $sort,
            'dir'         => $dir,
            'catId'       => $catId,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $formation = new Formation();
        $form      = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($formation);
            $em->flush();
            $this->addFlash('success', 'Formation créée avec succès.');
            return $this->redirectToRoute('dashboard_home');
        }

        return $this->render('dashboard/formation/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'Nouvelle formation',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request, FormationRepository $repo, EntityManagerInterface $em): Response
    {
        $formation = $repo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Formation modifiée avec succès.');
            return $this->redirectToRoute('dashboard_home');
        }

        return $this->render('dashboard/formation/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'Modifier la formation',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, FormationRepository $repo, EntityManagerInterface $em): Response
    {
        $formation = $repo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_form_' . $id, $request->request->get('_token'))) {
            $em->remove($formation);
            $em->flush();
            $this->addFlash('success', 'Formation supprimée.');
        }

        return $this->redirectToRoute('dashboard_home');
    }

    #[Route('/{id}/inscriptions', name: 'inscriptions')]
    public function inscriptions(int $id, FormationRepository $repo, InscriptionRepository $inscRepo): Response
    {
        $formation = $repo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        return $this->render('dashboard/formation/inscriptions.html.twig', [
            'formation'    => $formation,
            'inscriptions' => $inscRepo->findByFormation($id),
        ]);
    }
}
