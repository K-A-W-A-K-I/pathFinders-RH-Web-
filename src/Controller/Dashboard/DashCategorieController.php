<?php

namespace App\Controller\Dashboard;

use App\Entity\CategorieFormation;
use App\Form\CategorieFormationType;
use App\Repository\CategorieFormationRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/categories', name: 'dash_categorie_')]
class DashCategorieController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, CategorieFormationRepository $repo): Response
    {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'nom');
        $dir  = $request->query->get('dir', 'ASC');

        $categories = $q ? $repo->search($q) : $repo->findAllSorted($sort, $dir);

        return $this->render('dashboard/categorie/index.html.twig', [
            'categories' => $categories,
            'q'          => $q,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $categorie = new CategorieFormation();
        $form      = $this->createForm(CategorieFormationType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $categorie->setImage($imageFileName);
            }

            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie créée avec succès.');
            return $this->redirectToRoute('dashboard_home');
        }

        return $this->render('dashboard/categorie/form.html.twig', [
            'form'      => $form->createView(),
            'title'     => 'Nouvelle catégorie',
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request, CategorieFormationRepository $repo, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $categorie = $repo->find($id);
        if (!$categorie) throw $this->createNotFoundException();

        $form = $this->createForm(CategorieFormationType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la nouvelle image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($categorie->getImage()) {
                    $fileUploader->delete($categorie->getImage());
                }
                
                $imageFileName = $fileUploader->upload($imageFile);
                $categorie->setImage($imageFileName);
            }

            $em->flush();
            $this->addFlash('success', 'Catégorie modifiée avec succès.');
            return $this->redirectToRoute('dashboard_home');
        }

        return $this->render('dashboard/categorie/form.html.twig', [
            'form'      => $form->createView(),
            'title'     => 'Modifier la catégorie',
            'categorie' => $categorie,
            'isEdit'    => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, CategorieFormationRepository $repo, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $categorie = $repo->find($id);
        if (!$categorie) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_cat_' . $id, $request->request->get('_token'))) {
            // Supprimer l'image si elle existe
            if ($categorie->getImage()) {
                $fileUploader->delete($categorie->getImage());
            }
            
            $em->remove($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie supprimée.');
        }

        return $this->redirectToRoute('dashboard_home');
    }
}
