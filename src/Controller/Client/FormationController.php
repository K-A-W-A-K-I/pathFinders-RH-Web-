<?php

namespace App\Controller\Client;

use App\Entity\Inscription;
use App\Entity\InscriptionsFormation;
use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use App\Repository\InscriptionsFormationRepository;
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
        InscriptionRepository $inscriptionRepo,
        InscriptionsFormationRepository $inscFormRepo
    ): Response {
        $formation = $formationRepo->find($id);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) { $session->start(); $sessionId = $session->getId(); }

        $user      = $this->getUser();
        $isInscrit = false;

        if ($user) {
            // Vérifier dans inscriptions_formation
            $existing = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]);
            $isInscrit = $existing !== null;
        } else {
            // Fallback session
            $isInscrit = $inscriptionRepo->isAlreadyInscrit($sessionId, $id);
        }

        $contenuModules = $isInscrit ? $formation->getContenuModules() : [];

        // Calcul progression
        $totalModules = $formation->getContenuModules()->count();
        $modulesVus   = $session->get('modules_vus_' . $id, []);

        // Si connecté et session vide, restaurer depuis la base
        if ($user && empty($modulesVus) && $totalModules > 0) {
            $inscForm = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]);
            if ($inscForm && $inscForm->getPourcentage_progression() > 0) {
                $allMods = $formation->getContenuModules()->toArray();
                usort($allMods, fn($a, $b) => $a->getOrdre() <=> $b->getOrdre());
                $nbVusBase = (int)round((float)$inscForm->getPourcentage_progression() / 100 * $totalModules);
                for ($i = 0; $i < $nbVusBase && $i < count($allMods); $i++) {
                    $modulesVus[] = $allMods[$i]->getIdContenu();
                }
                $session->set('modules_vus_' . $id, $modulesVus);
            }
        }

        $nbVus       = count($modulesVus);
        $progression = $totalModules > 0 ? round(($nbVus / $totalModules) * 100) : 0;

        return $this->render('client/formations/detail.html.twig', [
            'formation'      => $formation,
            'isInscrit'      => $isInscrit,
            'contenuModules' => $contenuModules,
            'progression'    => $progression,
            'nbVus'          => $nbVus,
            'totalModules'   => $totalModules,
            'modulesVus'     => $modulesVus,
            'delaiDepasse'   => $formation->getDateFin() && new \DateTime() > $formation->getDateFin(),
        ]);
    }

    #[Route('/inscrire/{id}', name: 'formation_inscrire', methods: ['POST'])]
public function inscrire(
    int $id,
    Request $request,
    FormationRepository $formationRepo,
    InscriptionsFormationRepository $inscFormRepo,
    EntityManagerInterface $em
): Response {
    $formation = $formationRepo->find($id);
    if (!$formation) {
        throw $this->createNotFoundException('Formation introuvable.');
    }

    $user = $this->getUser();
    if (!$user) {
        $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à une formation.');
        return $this->redirectToRoute('auth_login');
    }

    $existing = $inscFormRepo->findOneBy([
        'utilisateur' => $user,
        'formation'   => $formation,
    ]);
    if ($existing) {
        $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation.');
        return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
    }

    if ($formation->getPlaceDisponible() <= 0) {
        $this->addFlash('error', 'Aucune place disponible pour cette formation.');
        return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
    }

    $inscForm = new InscriptionsFormation();
    $inscForm->setUtilisateur($user);
    $inscForm->setFormation($formation);
    $inscForm->setDate_inscription(new \DateTime());
    $inscForm->setPourcentage_progression('0');

    $formation->setPlaceDisponible($formation->getPlaceDisponible() - 1);

    $em->persist($inscForm);
    $em->flush();

    $this->addFlash('success', 'Inscription réussie ! Vous avez maintenant accès au contenu de la formation.');
    return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
}
    #[Route('/module/{id}', name: 'module_detail')]
    public function moduleDetail(
        int $id,
        Request $request,
        InscriptionRepository $inscriptionRepo,
        InscriptionsFormationRepository $inscFormRepo,
        EntityManagerInterface $em
    ): Response {
        $module = $em->getRepository(\App\Entity\ContenuModule::class)->find($id);
        if (!$module) {
            throw $this->createNotFoundException('Module introuvable.');
        }

        $formation = $module->getFormation();
        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) { $session->start(); $sessionId = $session->getId(); }

        $user      = $this->getUser();
        $isInscrit = false;

        if ($user) {
            $isInscrit = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]) !== null;
        } else {
            $isInscrit = $inscriptionRepo->isAlreadyInscrit($sessionId, $formation->getIdFormation());
        }

        if (!$isInscrit) {
            $this->addFlash('error', 'Vous devez être inscrit à la formation pour accéder à ce module.');
            return $this->redirectToRoute('client_formation_detail', ['id' => $formation->getIdFormation()]);
        }

        // Bloquer si progression = 100% (formation terminée, certificat obtenu)
        if ($user) {
            $inscForm = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]);
            if ($inscForm && (int)$inscForm->getPourcentage_progression() === 100) {
                $this->addFlash('info', 'Vous avez terminé cette formation et obtenu votre certificat. Accès aux modules fermé.');
                return $this->redirectToRoute('client_formation_detail', ['id' => $formation->getIdFormation()]);
            }
        }

        // Bloquer si délai dépassé et progression < 100% et pas de réinscription récente
        if ($formation->getDateFin()) {
            $now = new \DateTime();
            if ($now > $formation->getDateFin()) {
                $inscForm = $user ? $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]) : null;
                $prog = $inscForm ? (int)$inscForm->getPourcentage_progression() : 0;
                // Bloquer seulement si l'inscription date d'avant la date de fin
                if ($prog < 100 && $inscForm && $inscForm->getDate_inscription() < $formation->getDateFin()) {
                    $this->addFlash('error', 'Le délai de cette formation est dépassé. Vous pouvez vous réinscrire.');
                    return $this->redirectToRoute('mes_inscriptions_formation');
                }
            }
        }

        // Enregistrer ce module comme vu
        $sessionKey = 'modules_vus_' . $formation->getIdFormation();
        $modulesVus = $session->get($sessionKey, []);

        // Si connecté et session vide, restaurer depuis la base
        if ($user && empty($modulesVus)) {
            $inscForm = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]);
            if ($inscForm && $inscForm->getPourcentage_progression() > 0) {
                // Reconstruire les modules vus depuis la progression sauvegardée
                $allMods = $formation->getContenuModules()->toArray();
                usort($allMods, fn($a, $b) => $a->getOrdre() <=> $b->getOrdre());
                $totalMods = count($allMods);
                $nbVusBase = $totalMods > 0 ? (int)round((float)$inscForm->getPourcentage_progression() / 100 * $totalMods) : 0;
                for ($i = 0; $i < $nbVusBase && $i < $totalMods; $i++) {
                    $modulesVus[] = $allMods[$i]->getIdContenu();
                }
                $session->set($sessionKey, $modulesVus);
            }
        }

        if (!in_array($id, $modulesVus)) {
            $modulesVus[] = $id;
            $session->set($sessionKey, $modulesVus);
        }

        $totalModules = $formation->getContenuModules()->count();
        $nbVus        = count($modulesVus);
        $progression  = $totalModules > 0 ? round(($nbVus / $totalModules) * 100) : 0;

        // Sauvegarder la progression en base si connecté
        if ($user) {
            $inscForm = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]);
            if ($inscForm && (float)$inscForm->getPourcentage_progression() < $progression) {
                $inscForm->setPourcentage_progression((string)$progression);
                $em->flush();
            }
        }

        $allModules = $formation->getContenuModules()->toArray();
        usort($allModules, fn($a, $b) => $a->getOrdre() <=> $b->getOrdre());
        $currentIndex = array_search($module, $allModules);
        $prevModule   = $currentIndex > 0 ? $allModules[$currentIndex - 1] : null;
        $nextModule   = $currentIndex < count($allModules) - 1 ? $allModules[$currentIndex + 1] : null;

        return $this->render('client/modules/detail.html.twig', [
            'module'       => $module,
            'formation'    => $formation,
            'progression'  => $progression,
            'nbVus'        => $nbVus,
            'totalModules' => $totalModules,
            'modulesVus'   => $modulesVus,
            'prevModule'   => $prevModule,
            'nextModule'   => $nextModule,
        ]);
    }

    #[Route('/certificat/{id}', name: 'certificat')]
    public function certificat(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        InscriptionRepository $inscriptionRepo,
        InscriptionsFormationRepository $inscFormRepo
    ): Response {
        $formation = $formationRepo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        $session   = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) { $session->start(); $sessionId = $session->getId(); }

        $user      = $this->getUser();
        $isInscrit = $user
            ? $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]) !== null
            : $inscriptionRepo->isAlreadyInscrit($sessionId, $id);

        if (!$isInscrit) {
            $this->addFlash('error', 'Vous devez être inscrit pour obtenir un certificat.');
            return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
        }

        // Vérifier 100%
        $totalModules = $formation->getContenuModules()->count();
        $modulesVus   = $session->get('modules_vus_' . $id, []);
        $progression  = $totalModules > 0 ? round((count($modulesVus) / $totalModules) * 100) : 0;

        if ($progression < 100) {
            $this->addFlash('warning', 'Vous devez compléter tous les modules pour obtenir votre certificat.');
            return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
        }

        $nomParticipant = $user ? $user->getFullName() : 'Ranim Wadrani';
        $dateObtention  = new \DateTime();

        // Générer le HTML du certificat
        $html = $this->renderView('client/certificat_pdf.html.twig', [
            'formation'      => $formation,
            'nomParticipant' => $nomParticipant,
            'dateObtention'  => $dateObtention,
        ]);

        // Générer le PDF avec Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->getOptions()->setChroot($this->getParameter('kernel.project_dir') . '/public');
        $dompdf->getOptions()->setIsRemoteEnabled(true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'certificat_' . preg_replace('/[^a-z0-9]/i', '_', $formation->getTitre()) . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/reinscire/{id}', name: 'formation_reinscire', methods: ['POST'])]
    public function reinscire(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        InscriptionsFormationRepository $inscFormRepo,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('auth_login');

        // Supprimer l'ancienne inscription et flush immédiatement
        $existing = $inscFormRepo->findOneBy(['utilisateur' => $user, 'formation' => $formation]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        // Vérifier places disponibles
        if ($formation->getPlaceDisponible() <= 0) {
            $this->addFlash('error', 'Aucune place disponible.');
            return $this->redirectToRoute('mes_inscriptions_formation');
        }

        // Nouvelle inscription
        $formation->setPlaceDisponible($formation->getPlaceDisponible() - 1);
        $inscForm = new \App\Entity\InscriptionsFormation();
        $inscForm->setUtilisateur($user);
        $inscForm->setFormation($formation);
        $inscForm->setDate_inscription(new \DateTime());
        $inscForm->setPourcentage_progression('0');
        $em->persist($inscForm);
        $em->flush();

        // Réinitialiser la progression en session
        $request->getSession()->remove('modules_vus_' . $id);

        $this->addFlash('success', 'Réinscription réussie ! Vous avez de nouveau accès aux modules.');
        return $this->redirectToRoute('client_formation_detail', ['id' => $id]);
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
