<?php

namespace App\Controller\FichePaie;

use App\Entity\FichesPaiement;
use App\Form\FichesPaiementType;
use App\Repository\FichesPaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class FichesPaiementController extends AbstractController
{
    // ── 1. INDEX ──────────────────────────────────────
    #[Route('/fiches', name: 'fiche_index')]
    public function index(FichesPaiementRepository $repo): Response
    {
        return $this->render('fiches_paiement/index.html.twig', [
            'fiches' => $repo->findAll()
        ]);
    }

    // ── 2. NEW ────────────────────────────────────────
    #[Route('/fiches/new', name: 'fiche_new', methods: ['GET', 'POST'])]
   public function new(Request $request, EntityManagerInterface $em, FichesPaiementRepository $repo): Response
{
    $fiche = new FichesPaiement();
    $form = $this->createForm(FichesPaiementType::class, $fiche);
    $form->handleRequest($request);

    if ($form->isSubmitted() && !$form->isValid()) {
        foreach ($form->getErrors(true) as $error) {
            dump($error->getMessage());
        }
        dd('end of errors');
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $employee = $fiche->getEmployee();
        $date = $fiche->getDatePaiement();

        // ── CHECK: only one fiche per employee per month ──
        if ($date && $repo->findOneBy([
            'employee' => $employee,
            'date_paiement' => new \DateTime($date->format('Y-m-01')) // match the first day of month
        ])) {
            $this->addFlash('error', 'Une fiche pour ce mois existe déjà pour cet employé.pour des changements, veuillez éditer la fiche existante.');
            return $this->redirectToRoute('fiche_new');
        }

        $salaireMensuel = $employee->getSalaire() ?? 0;
        $salaireAnnuel = $salaireMensuel * 12;

        $taxe = FichesPaiement::calculerTaxe($salaireAnnuel);
        $fiche->setMontantTaxe($taxe);

        $score = $employee->getScore() ?? 0;
        $deduction = ((100 - $score) / 100) * $salaireMensuel * 0.1;
        $fiche->setMontantDeduction(round(abs($deduction), 2));

        $em->persist($fiche);
        $em->flush();

        $this->addFlash('success', 'Fiche créée avec succès !');
        return $this->redirectToRoute('fiche_index');
    }

    return $this->render('fiches_paiement/new.html.twig', [
        'form' => $form->createView(),
    ]);
}

    // ── 3. PRINT ALL ──────────────────────────────────
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

    // ── 4. WORKER VIEW ────────────────────────────────
    #[Route('/mes-fiches', name: 'worker_fiches')]
    public function workerIndex(
        FichesPaiementRepository $repo,
        \App\Repository\EmployeeRepository $employeeRepo
    ): Response {
        $user     = $this->getUser();
        $employee = $user ? $employeeRepo->findOneBy(['utilisateur' => $user]) : null;
        $fiches   = $employee ? $repo->findBy(['employee' => $employee]) : [];

        return $this->render('worker/fiches.html.twig', [
            'fiches'   => $fiches,
            'employee' => $employee,
        ]);
    }

    // ── 5. SHOW ───────────────────────────────────────
    #[Route('/fiches/{id}', name: 'fiche_show', methods: ['GET'])]
    public function show(FichesPaiement $fiche): Response
    {
        return $this->render('fiches_paiement/show.html.twig', [
            'fiche' => $fiche,
        ]);
    }

    // ── 6. EDIT ───────────────────────────────────────
    #[Route('/fiches/{id}/edit', name: 'fiche_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FichesPaiement $fiche, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FichesPaiementType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                dump($error->getMessage());
            }
            dd('end of errors');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $employee = $fiche->getEmployee();
            $salaireMensuel = $employee->getSalaire() ?? 0;
            $salaireAnnuel = $salaireMensuel * 12;

            $taxe = FichesPaiement::calculerTaxe($salaireAnnuel);
            $fiche->setMontantTaxe($taxe);

            $score = $employee->getScore() ?? 0;
            $deduction = ((100 - $score) / 100) * $salaireMensuel * 0.1;
            $fiche->setMontantDeduction(round(abs($deduction), 2));

            $em->flush();

            $this->addFlash('success', 'Fiche modifiée avec succès !');
            return $this->redirectToRoute('fiche_index');
        }

        return $this->render('fiches_paiement/edit.html.twig', [
            'form' => $form->createView(),
            'fiche' => $fiche,
        ]);
    }

    // ── 7. PRINT ONE ──────────────────────────────────
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

    // ── 8. DELETE ─────────────────────────────────────
    #[Route('/fiches/{id}/delete', name: 'fiche_delete', methods: ['POST'])]
    public function delete(FichesPaiement $fiche, EntityManagerInterface $em): Response
    {
        $em->remove($fiche);
        $em->flush();

        $this->addFlash('success', 'Fiche supprimée.');
        return $this->redirectToRoute('fiche_index');
    }
}