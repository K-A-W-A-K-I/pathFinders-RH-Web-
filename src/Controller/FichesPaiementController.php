<?php

namespace App\Controller;

use App\Entity\FichesPaiement;
use App\Form\FichesPaiementType;
use App\Repository\FichesPaiementRepository;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// add these imports at the top
use Dompdf\Dompdf;
use Dompdf\Options;

class FichesPaiementController extends AbstractController
{
    #[Route('/fiches', name: 'fiche_index')]
    public function index(FichesPaiementRepository $repo): Response
    {
        return $this->render('fiches_paiement/index.html.twig', [
            'fiches' => $repo->findAll()
        ]);
    }

    #[Route('/fiches/new', name: 'fiche_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $fiche = new FichesPaiement();
        $form = $this->createForm(FichesPaiementType::class, $fiche);
        $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
    // calculate deduction BEFORE persist
    $score = $fiche->getEmployee()->getScore() ?? 0;
    $salaire = $fiche->getEmployee()->getSalaire() ?? 0;
    $deduction = ((100 - $score) / 100) * $salaire * 0.1;
    $fiche->setMontantDeduction(round($deduction, 2));

    $em->persist($fiche);
    $em->flush();

    $this->addFlash('success', 'Fiche de paiement créée avec succès !');
    return $this->redirectToRoute('fiche_index');
}

        return $this->render('fiches_paiement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

   #[Route('/fiches/{id}/edit', name: 'fiche_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, FichesPaiement $fiche, EntityManagerInterface $em): Response
{
    $form = $this->createForm(FichesPaiementType::class, $fiche);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // recalculate deduction
        $score = $fiche->getEmployee()->getScore() ?? 0;
        $fiche->setMontantDeduction($score * 0.5);

        $em->flush();

        $this->addFlash('success', 'Fiche modifiée avec succès !');
        return $this->redirectToRoute('fiche_index');
    }

    return $this->render('fiches_paiement/edit.html.twig', [
        'form' => $form->createView(),
        'fiche' => $fiche,
    ]);
}

    #[Route('/fiches/{id}/delete', name: 'fiche_delete', methods: ['POST'])]
    public function delete(FichesPaiement $fiche, EntityManagerInterface $em): Response
    {
        $em->remove($fiche);
        $em->flush();

        return $this->redirectToRoute('fiche_index');
    }
    #[Route('/mes-fiches', name: 'worker_fiches')]
public function workerIndex(FichesPaiementRepository $repo): Response
{
    return $this->render('worker/fiches.html.twig', [
        'fiches' => $repo->findAll()
    ]);
}
// ── PRINT ALL ──────────────────────────────────────────
#[Route('/fiches/print', name: 'fiche_print_all')]
public function printAll(FichesPaiementRepository $repo): Response
{
    $fiches = $repo->findAll();

    $html = $this->renderView('fiches_paiement/pdf_all.html.twig', [
        'fiches' => $fiches
    ]);

    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="fiches_paiement.pdf"',
        ]
    );
}

// ── PRINT ONE ──────────────────────────────────────────
#[Route('/fiches/{id}/print', name: 'fiche_print_one')]
public function printOne(FichesPaiement $fiche): Response
{
    $html = $this->renderView('fiches_paiement/pdf_one.html.twig', [
        'fiche' => $fiche
    ]);

    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="fiche_' . $fiche->getId() . '.pdf"',
        ]
    );
}


}