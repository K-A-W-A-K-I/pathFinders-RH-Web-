<?php

namespace App\Controller;

use App\Entity\Prime;
use App\Form\PrimeType;
use App\Repository\PrimeRepository;
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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $prime = new Prime();
        $form = $this->createForm(PrimeType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // duplicate check
            $existing = $em->getRepository(Prime::class)->createQueryBuilder('p')
                ->where('p.fichesPaiement = :fiche')
                ->andWhere('p.dateAttribution = :date')
                ->setParameter('fiche', $prime->getFichesPaiement())
                ->setParameter('date', $prime->getDateAttribution())
                ->getQuery()
                ->getResult();

            if (count($existing) > 0) {
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

    #[Route('/primes/{id}/delete', name: 'prime_delete', methods: ['POST'])]
    public function delete(Prime $prime, EntityManagerInterface $em): Response
    {
        $em->remove($prime);
        $em->flush();
        $this->addFlash('success', 'Prime supprimée.');
        return $this->redirectToRoute('prime_index');
    }
    #[Route('/mes-primes', name: 'worker_primes')]
public function workerIndex(PrimeRepository $repo): Response
{
    return $this->render('worker/primes.html.twig', [
        'primes' => $repo->findAll()
    ]);
}
}