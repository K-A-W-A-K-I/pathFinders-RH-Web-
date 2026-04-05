<?php

namespace App\Controller;

use App\Entity\Prime;
use App\Entity\FichesPaiement;
use App\Form\PrimeType;
use App\Repository\PrimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrimeController extends AbstractController
{
    // ── TELEGRAM ──────────────────────────────────────
    private function sendTelegram(string $message): void
    {
        try {
            $url = "https://api.telegram.org/bot8314501170:AAFxrRneIDQEA7F_2F3R_T1vFcmLk50wEwI/sendMessage?chat_id=6421140204&text=" . urlencode($message);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // fail silently
        }
    }

    // ── INDEX ─────────────────────────────────────────
    #[Route('/primes', name: 'prime_index')]
    public function index(PrimeRepository $repo): Response
    {
        return $this->render('prime/index.html.twig', [
            'primes' => $repo->findAll()
        ]);
    }

    // ── NEW ───────────────────────────────────────────
    #[Route('/primes/new', name: 'prime_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $prime = new Prime();
        $form = $this->createForm(PrimeType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $existing = $em->getRepository(Prime::class)->createQueryBuilder('p')
                ->where('p.fichesPaiement = :fiche')
                ->andWhere('p.dateAttribution = :date')
                ->setParameter('fiche', $prime->getFichesPaiement())
                ->setParameter('date', $prime->getDateAttribution())
                ->getQuery()
                ->getResult();

            if (count($existing) > 0) {
                // get employee name
                $nom    = $prime->getFichesPaiement()->getEmployee()->getUtilisateur()->getNom();
                $prenom = $prime->getFichesPaiement()->getEmployee()->getUtilisateur()->getPrenom();

                // send telegram alert
                $this->sendTelegram(
                    "⚠ Activité suspecte détectée !\n" .
                    "Employé : {$nom} {$prenom}\n" .
                    "Tentative de prime en double détectée."
                );

                $this->addFlash('error', 'Une prime existe déjà pour cette fiche à cette date.');
            } else {
                $em->persist($prime);
                $em->flush();
                $this->addFlash('success', 'Prime créée avec succès !');
                return $this->redirectToRoute('prime_index');
            }
        }

        return $this->render('prime/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ── NEW FOR FICHE ─────────────────────────────────
    #[Route('/primes/new-for-fiche/{id}', name: 'prime_new_for_fiche', methods: ['GET', 'POST'])]
    public function newForFiche(
        Request $request,
        FichesPaiement $fiche,
        EntityManagerInterface $em
    ): Response {
        $prime = new Prime();
        $prime->setFichesPaiement($fiche);

        $form = $this->createForm(PrimeType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $existing = $em->getRepository(Prime::class)->createQueryBuilder('p')
                ->where('p.fichesPaiement = :fiche')
                ->andWhere('p.dateAttribution = :date')
                ->setParameter('fiche', $prime->getFichesPaiement())
                ->setParameter('date', $prime->getDateAttribution())
                ->getQuery()
                ->getResult();

            if (count($existing) > 0) {
                $nom    = $prime->getFichesPaiement()->getEmployee()->getUtilisateur()->getNom();
                $prenom = $prime->getFichesPaiement()->getEmployee()->getUtilisateur()->getPrenom();

                $this->sendTelegram(
                    "⚠ Activité suspecte détectée !\n" .
                    "Employé : {$nom} {$prenom}\n" .
                    "Tentative de prime en double détectée."
                );

                $this->addFlash('error', 'Une prime existe déjà pour cette fiche à cette date.');
            } else {
                $em->persist($prime);
                $em->flush();
                $this->addFlash('success', 'Prime ajoutée avec succès !');
                return $this->redirectToRoute('fiche_index');
            }
        }

        return $this->render('prime/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ── EDIT ──────────────────────────────────────────
    #[Route('/primes/{id}/edit', name: 'prime_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Prime $prime, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PrimeType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Prime modifiée avec succès !');
            return $this->redirectToRoute('prime_index');
        }

        return $this->render('prime/edit.html.twig', [
            'form' => $form->createView(),
            'prime' => $prime,
        ]);
    }

    // ── DELETE ────────────────────────────────────────
    #[Route('/primes/{id}/delete', name: 'prime_delete', methods: ['POST'])]
    public function delete(Prime $prime, EntityManagerInterface $em): Response
    {
        $ficheId = $prime->getFichesPaiement()?->getIdFichePaiement();

        $em->remove($prime);
        $em->flush();

        $this->addFlash('success', 'Prime supprimée.');

        if ($ficheId) {
            return $this->redirectToRoute('fiche_show', ['id' => $ficheId]);
        }

        return $this->redirectToRoute('prime_index');
    }

    // ── WORKER VIEW ───────────────────────────────────
    #[Route('/mes-primes', name: 'worker_primes')]
    public function workerIndex(PrimeRepository $repo): Response
    {
        return $this->render('worker/primes.html.twig', [
            'primes' => $repo->findAll()
        ]);
    }
}