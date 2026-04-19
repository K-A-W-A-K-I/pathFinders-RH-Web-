<?php

namespace App\Service;

use App\Entity\Candidat;
use App\Entity\Utilisateur;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class UserPdfGenerator
{
    private string $companyName;

    public function __construct(
        private readonly Environment $twig,
        ?string $companyName,
    ) {
        $this->companyName = (string) ($companyName ?? 'PathFinders RH');
    }

    private function createDompdf(string $orientation = 'portrait'): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', $orientation);

        return $dompdf;
    }

    public function generateUserProfilePdf(Utilisateur $user, ?Candidat $candidat): string
    {
        $html = $this->twig->render('user/pdf/profile.html.twig', [
            'user' => $user,
            'candidat' => $candidat,
            'company_name' => $this->companyName,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $dompdf = $this->createDompdf('portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param Utilisateur[] $users
     */
    public function generateAllUsersPdf(array $users): string
    {
        $html = $this->twig->render('user/pdf/all_users.html.twig', [
            'users' => $users,
            'company_name' => $this->companyName,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $dompdf = $this->createDompdf('landscape');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
