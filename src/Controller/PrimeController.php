<?php

namespace App\Controller;

use App\Entity\Prime;
use App\Repository\PrimeRepository;
use App\Repository\FichesPaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrimeController extends AbstractController
{
    #[Route('/primes', name: 'prime_index')]
    public function index(PrimeRepository $repo): Response
    {
        return $this->render('prime/index.html.twig', [
            'primes' => $repo->findAll()
        ]);
    }

    #[Route('/primes/new', name: 'prime_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FichesPaiementRepository $ficheRepo): Response
    {
        $fiches = $ficheRepo->findAll();
        $error = null;

        if ($request->isMethod('POST')) {
            $fiche = $ficheRepo->find($request->request->get('fiche'));
            $date = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_attribution'));

            // ✅ Check: no duplicate prime on same day for same fiche
            $existing = $em->getRepository(Prime::class)->createQueryBuilder('p')
                ->where('p.fichesPaiement = :fiche')
                ->andWhere('p.dateAttribution = :date')
                ->setParameter('fiche', $fiche)
                ->setParameter('date', $date)
                ->getQuery()
                ->getResult();

            if (count($existing) > 0) {
                $error = "Une prime existe déjà pour cette fiche à cette date.";
            } else {
                $prime = new Prime();
                $prime->setLibelle($request->request->get('libelle'));
                $prime->setMontant($request->request->get('montant'));
                $prime->setTypePrime($request->request->get('type_prime'));
                $prime->setDateAttribution($date);
                $prime->setFichesPaiement($fiche);

                $em->persist($prime);
                $em->flush();

                // ✅ Recalculate total primes on the fiche after adding
                $this->recalculerPrimesFiche($fiche, $em);

                return $this->redirectToRoute('prime_index');
            }
        }

        return $this->render('prime/new.html.twig', [
            'fiches' => $fiches,
            'error' => $error
        ]);
    }

    #[Route('/primes/{id}/edit', name: 'prime_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Prime $prime, EntityManagerInterface $em, FichesPaiementRepository $ficheRepo): Response
    {
        $fiches = $ficheRepo->findAll();

        if ($request->isMethod('POST')) {
            $fiche = $ficheRepo->find($request->request->get('fiche'));
            $date = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_attribution'));

            $prime->setLibelle($request->request->get('libelle'));
            $prime->setMontant($request->request->get('montant'));
            $prime->setTypePrime($request->request->get('type_prime'));
            $prime->setDateAttribution($date);
            $prime->setFichesPaiement($fiche);

            $em->flush();

            // ✅ Recalculate after edit too
            $this->recalculerPrimesFiche($fiche, $em);

            return $this->redirectToRoute('prime_index');
        }

        return $this->render('prime/edit.html.twig', [
            'prime' => $prime,
            'fiches' => $fiches
        ]);
    }

    #[Route('/primes/{id}/delete', name: 'prime_delete', methods: ['POST'])]
    public function delete(Prime $prime, EntityManagerInterface $em): Response
    {
        $fiche = $prime->getFichesPaiement();

        $em->remove($prime);
        $em->flush();

        // ✅ Recalculate after delete too
        if ($fiche) {
            $this->recalculerPrimesFiche($fiche, $em);
        }

        return $this->redirectToRoute('prime_index');
    }

    // ✅ Equivalent of calculerPrimesParId() from Java
    private function recalculerPrimesFiche($fiche, EntityManagerInterface $em): void
    {
        $total = 0;
        foreach ($fiche->getPrimes() as $p) {
            $total += $p->getMontant();
        }
        // if you restore the primes float column later, set it here
        // $fiche->setPrimesTotal($total);
        // $em->flush();
    }
}