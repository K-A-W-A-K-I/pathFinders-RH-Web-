<?php

namespace App\Controller\FichePaie;

use App\Entity\FichesPaiement;
use App\Form\FichesPaiementType;
use App\Repository\EmployeeRepository;
use App\Repository\FichesPaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

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
        $form  = $this->createForm(FichesPaiementType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employee = $fiche->getEmployee();
            $date     = $fiche->getDatePaiement();

            // Only one fiche per employee per month
            if ($date && $repo->findOneBy([
                'employee'      => $employee,
                'date_paiement' => new \DateTime($date->format('Y-m-01')),
            ])) {
                $this->addFlash('error', 'Une fiche pour ce mois existe déjà pour cet employé. Pour des changements, veuillez éditer la fiche existante.');
                return $this->redirectToRoute('fiche_new');
            }

            $salaireMensuel = $employee->getSalaire() ?? 0;
            $salaireAnnuel  = $salaireMensuel * 12;

            $taxe = FichesPaiement::calculerTaxe($salaireAnnuel);
            $fiche->setMontantTaxe($taxe);

            $score     = $employee->getScore() ?? 0;
            $deduction = ((100 - $score) / 100) * $salaireMensuel * 0.1;
            $fiche->setMontantDeduction(round(abs($deduction), 2));

            $em->persist($fiche);
            $em->flush();

            // Send confirmation email via Gmail
            $emailAddress = $employee->getUtilisateur()?->getEmail();
            $fullName     = $employee->getUtilisateur()?->getFullName() ?? 'Employé';
            $mois         = $fiche->getDatePaiement()?->format('F Y') ?? '';
            $salaireNet   = $fiche->getSalaireNet();
            $salaireBrut  = $salaireMensuel;
            $montantTaxe  = $fiche->getMontantTaxe();
            $montantDed   = $fiche->getMontantDeduction();

            if ($emailAddress) {
                try {
                    $gmailDsn = $_ENV['MAILER_DSN_GMAIL'] ?? '';
                    $transport = Transport::fromDsn($gmailDsn);
                    $mailer    = new Mailer($transport);

                    $html = "
                    <div style='font-family:sans-serif;max-width:580px;margin:0 auto;background:#f9f9ff;border-radius:14px;overflow:hidden'>
                        <div style='background:linear-gradient(135deg,#6C63FF,#9D6BFF);padding:28px 32px'>
                            <h1 style='color:#fff;margin:0;font-size:22px;font-weight:700'>💰 Fiche de paie disponible</h1>
                            <p style='color:rgba(255,255,255,.8);margin:6px 0 0;font-size:14px'>{$mois}</p>
                        </div>
                        <div style='padding:28px 32px;background:#fff'>
                            <p style='color:#333;font-size:15px'>Bonjour <strong>{$fullName}</strong>,</p>
                            <p style='color:#555;font-size:14px'>Votre fiche de paie pour le mois de <strong>{$mois}</strong> a été générée par le service RH.</p>

                            <div style='background:#f8f7ff;border:1px solid rgba(108,99,255,.15);border-radius:10px;padding:20px;margin:20px 0'>
                                <table style='width:100%;border-collapse:collapse'>
                                    <tr>
                                        <td style='padding:8px 0;color:#666;font-size:13px'>Salaire brut</td>
                                        <td style='padding:8px 0;text-align:right;font-weight:600;color:#333'>{$salaireBrut} DT</td>
                                    </tr>
                                    <tr style='border-top:1px solid rgba(108,99,255,.1)'>
                                        <td style='padding:8px 0;color:#e24b4a;font-size:13px'>Taxe</td>
                                        <td style='padding:8px 0;text-align:right;font-weight:600;color:#e24b4a'>- {$montantTaxe} DT</td>
                                    </tr>
                                    <tr style='border-top:1px solid rgba(108,99,255,.1)'>
                                        <td style='padding:8px 0;color:#e24b4a;font-size:13px'>Déduction</td>
                                        <td style='padding:8px 0;text-align:right;font-weight:600;color:#e24b4a'>- {$montantDed} DT</td>
                                    </tr>
                                    <tr style='border-top:2px solid rgba(108,99,255,.2)'>
                                        <td style='padding:12px 0;color:#6C63FF;font-size:15px;font-weight:700'>Salaire net</td>
                                        <td style='padding:12px 0;text-align:right;font-size:18px;font-weight:800;color:#6C63FF'>{$salaireNet} DT</td>
                                    </tr>
                                </table>
                            </div>

                            <p style='color:#555;font-size:13px'>Connectez-vous à votre espace PathFinder pour consulter le détail complet et télécharger votre fiche en PDF.</p>
                            <p style='color:#999;font-size:12px;margin-top:24px;border-top:1px solid #eee;padding-top:16px'>PathFinders RH — Service de paie</p>
                        </div>
                    </div>";

                    $email = (new Email())
                        ->from('boulehmifadi95@gmail.com')
                        ->to($emailAddress)
                        ->subject("💰 Fiche de paie {$mois} — PathFinders RH")
                        ->html($html);

                    $mailer->send($email);
                } catch (\Throwable) {
                    // Don't block if email fails
                }
            }

            $this->addFlash('success', 'Fiche créée avec succès ! Un email de confirmation a été envoyé à l\'employé.');
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

    // ── 4. WORKER: current month ──────────────────────
    #[Route('/mes-fiches', name: 'worker_fiches')]
    public function workerIndex(FichesPaiementRepository $repo, EmployeeRepository $employeeRepo): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return $this->redirectToRoute('auth_login');
        }

        $employee = $employeeRepo->findOneBy(['utilisateur' => $utilisateur]);

        return $this->render('worker/fiches.html.twig', [
            'fiches'    => $employee ? $repo->findCurrentMonthByEmployee($employee->getIdEmployee()) : [],
            'employee'  => $employee,
            'isHistory' => false,
        ]);
    }

    // ── 4b. WORKER: history ───────────────────────────
    #[Route('/mes-fiches/historique', name: 'worker_fiches_history')]
    public function workerHistory(FichesPaiementRepository $repo, EmployeeRepository $employeeRepo): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return $this->redirectToRoute('auth_login');
        }

        $employee = $employeeRepo->findOneBy(['utilisateur' => $utilisateur]);

        return $this->render('worker/fiches.html.twig', [
            'fiches'    => $employee ? $repo->findAllByEmployee($employee->getIdEmployee()) : [],
            'employee'  => $employee,
            'isHistory' => true,
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

        if ($form->isSubmitted() && $form->isValid()) {
            $employee       = $fiche->getEmployee();
            $salaireMensuel = $employee->getSalaire() ?? 0;
            $salaireAnnuel  = $salaireMensuel * 12;

            $taxe = FichesPaiement::calculerTaxe($salaireAnnuel);
            $fiche->setMontantTaxe($taxe);

            $score     = $employee->getScore() ?? 0;
            $deduction = ((100 - $score) / 100) * $salaireMensuel * 0.1;
            $fiche->setMontantDeduction(round(abs($deduction), 2));

            $em->flush();

            $this->addFlash('success', 'Fiche modifiée avec succès !');
            return $this->redirectToRoute('fiche_index');
        }

        return $this->render('fiches_paiement/edit.html.twig', [
            'form'  => $form->createView(),
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

    // ── 9. AI PRIME RECOMMENDATION ────────────────────
    #[Route('/fiches/{id}/recommend-prime', name: 'fiche_recommend_prime', methods: ['GET'])]
    public function recommendPrime(FichesPaiement $fiche): Response
    {
        $employee    = $fiche->getEmployee();
        $utilisateur = $employee->getUtilisateur();
        $score       = $employee->getScore() ?? 0;
        $salaire     = $employee->getSalaire() ?? 0;
        $deduction   = $fiche->getMontantDeduction() ?? 0;
        $salaireNet  = $fiche->getSalaireNet() ?? 0;
        $typePaie    = $fiche->getTypePaiement() ?? 'mensuel';
        $nom         = $utilisateur->getNom() . ' ' . $utilisateur->getPrenom();

        $performanceLevel = match(true) {
            $score >= 85 => 'excellente',
            $score >= 70 => 'bonne',
            $score >= 50 => 'moyenne',
            default      => 'faible',
        };

        $prompt = "Tu es un expert RH tunisien. 
Basé sur ces données d'employé, recommande une prime avec un montant précis en DT et une justification courte en français.

Employé: {$nom}
Score de performance global (intègre les absences, ponctualité, rendement): {$score}/100
Niveau de performance: {$performanceLevel}
Salaire de base: {$salaire} DT
Déduction appliquée ce mois: {$deduction} DT (reflète les absences/retards)
Salaire net perçu: {$salaireNet} DT
Type de paiement: {$typePaie}
Mois: " . date('F Y') . "

Critères à considérer pour la prime:
- Un score élevé (>= 85) justifie une prime de performance généreuse
- Une déduction faible indique peu d'absences, donc prime de présence possible
- Si le salaire net est très inférieur au brut (grosse déduction), éviter une prime trop faible qui démoralise
- Le type de paiement peut influencer le montant (ex: contractuel vs permanent)

Réponds UNIQUEMENT par un objet JSON valide, sans texte avant ou après, sans backticks.
Format exact:
{
  \"type_prime\": \"performance|anciennete|exceptionnelle|presence\",
  \"montant\": 250,
  \"justification\": \"Explication courte ici\"
}";

        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $url    = 'https://api.groq.com/openai/v1/chat/completions';

        $payload = json_encode([
            'model'       => 'llama-3.1-8b-instant',
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $recommendation = null;
        $error          = null;

        try {
            $data = json_decode($response, true);

            if (isset($data['error'])) {
                $error = 'Groq error: ' . $data['error']['message'];
            } else {
                $text = trim($data['choices'][0]['message']['content'] ?? '');
                $text = preg_replace('/^```json\s*/i', '', $text);
                $text = preg_replace('/```$/', '', trim($text));

                if (preg_match('/\{.*\}/s', $text, $matches)) {
                    $recommendation = json_decode($matches[0], true);
                }

                if (!isset($recommendation['type_prime'], $recommendation['montant'], $recommendation['justification'])) {
                    $error          = 'Réponse invalide ou incomplète.';
                    $recommendation = null;
                }
            }
        } catch (\Exception $e) {
            $error = 'Exception: ' . $e->getMessage();
        }

        return $this->render('fiches_paiement/recommend.html.twig', [
            'fiche'          => $fiche,
            'recommendation' => $recommendation,
            'error'          => $error,
        ]);
    }
}
