<?php

namespace App\Controller\Client;

use App\Entity\Inscription;
use App\Form\InscriptionType;
use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formations', name: 'client_')]
class FormationController extends AbstractController
{
    #[Route('/categorie/{id}', name: 'formations_by_categorie')]
    public function byCategorie(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        CategorieFormationRepository $categorieRepo
    ): Response {
        $categorie = $categorieRepo->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'titre');
        $dir  = $request->query->get('dir', 'ASC');

        $formations = $q
            ? $formationRepo->search($q, $id, $sort, $dir)
            : $formationRepo->findByCategorieAndSort($id, $sort, $dir);

        return $this->render('client/formations/index.html.twig', [
            'categorie'  => $categorie,
            'formations' => $formations,
            'q'          => $q,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    #[Route('/detail/{id}', name: 'formation_detail')]
    public function detail(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        InscriptionRepository $inscriptionRepo
    ): Response {
        $formation = $formationRepo->find($id);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) {
            $session->start();
            $sessionId = $session->getId();
        }

        $isInscrit = $inscriptionRepo->isAlreadyInscrit($sessionId, $id);
        $contenuModules = $isInscrit ? $formation->getContenuModules() : [];

        return $this->render('client/formations/detail.html.twig', [
            'formation'      => $formation,
            'isInscrit'      => $isInscrit,
            'contenuModules' => $contenuModules,
        ]);
    }

    #[Route('/inscrire/{id}', name: 'formation_inscrire', methods: ['POST'])]
    public function inscrire(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        InscriptionRepository $inscriptionRepo,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepo->find($id);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) {
            $session->start();
            $sessionId = $session->getId();
        }

        // Vérifier si déjà inscrit
        $isInscrit = $inscriptionRepo->isAlreadyInscrit($sessionId, $id);
        if ($isInscrit) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation.');
            return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
        }

        // Vérifier les places disponibles
        if ($formation->getPlaceDisponible() <= 0) {
            $this->addFlash('error', 'Aucune place disponible pour cette formation.');
            return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
        }

        // Créer l'inscription
        $inscription = new Inscription();
        $inscription->setSessionId($sessionId);
        $inscription->setFormation($formation);
        $inscription->setNomParticipant('Ranim');
        $inscription->setEmailParticipant('ranim_wadrani@gmail.com');

        // Décrémenter les places disponibles
        $formation->setPlaceDisponible($formation->getPlaceDisponible() - 1);

        $em->persist($inscription);
        $em->flush();

        $this->addFlash('success', 'Inscription réussie ! Vous avez maintenant accès au contenu de la formation.');
        return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
    }

    #[Route('/module/{id}', name: 'module_detail')]
    public function moduleDetail(
        int $id,
        Request $request,
        InscriptionRepository $inscriptionRepo,
        EntityManagerInterface $em
    ): Response {
        $module = $em->getRepository(\App\Entity\ContenuModule::class)->find($id);
        if (!$module) {
            throw $this->createNotFoundException('Module introuvable.');
        }

        $formation = $module->getFormation();
        
        // Vérifier si l'utilisateur est inscrit à la formation
        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) {
            $session->start();
            $sessionId = $session->getId();
        }

        $isInscrit = $inscriptionRepo->isAlreadyInscrit($sessionId, $formation->getIdFormation());
        
        if (!$isInscrit) {
            $this->addFlash('error', 'Vous devez être inscrit à la formation pour accéder à ce module.');
            return $this->redirectToRoute('client_formation_detail', ['id' => $formation->getIdFormation()]);
        }

        return $this->render('client/modules/detail.html.twig', [
            'module'    => $module,
            'formation' => $formation,
        ]);
    }

    #[Route('/mes-inscriptions', name: 'mes_inscriptions')]
    public function mesInscriptions(
        Request $request,
        InscriptionRepository $inscriptionRepo
    ): Response {
        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) {
            $session->start();
            $sessionId = $session->getId();
        }

        $inscriptions = $inscriptionRepo->findBy(['sessionId' => $sessionId], ['dateInscription' => 'DESC']);

        return $this->render('client/inscriptions/index.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }
}
