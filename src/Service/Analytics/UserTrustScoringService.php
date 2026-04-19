<?php

namespace App\Service\Analytics;

use App\Entity\Utilisateur;

class UserTrustScoringService
{
    /**
     * @param array{total:int,open:int,rejected:int,review:int,resolved:int,repondu:int} $stats
     * @return array{score:float,level:string,flags:array<int,string>}
     */
    public function score(Utilisateur $user, array $stats): array
    {
        $total = max(0, (int) ($stats['total'] ?? 0));
        $open = max(0, (int) ($stats['open'] ?? 0));
        $rejected = max(0, (int) ($stats['rejected'] ?? 0));
        $review = max(0, (int) ($stats['review'] ?? 0));
        $resolved = max(0, (int) ($stats['resolved'] ?? 0));
        $repondu = max(0, (int) ($stats['repondu'] ?? 0));

        $score = 100.0;
        $flags = [];

        if ($total > 0) {
            $openRatio = $open / $total;
            $rejectedRatio = $rejected / $total;
            $reviewRatio = $review / $total;
            $closedRatio = ($resolved + $repondu) / $total;

            $score -= $openRatio * 25.0;
            $score -= $rejectedRatio * 30.0;
            $score -= $reviewRatio * 18.0;
            $score += $closedRatio * 8.0;

            if ($openRatio >= 0.5) {
                $flags[] = 'trop_de_reclamations_ouvertes';
            }
            if ($rejectedRatio >= 0.35) {
                $flags[] = 'ratio_rejet_eleve';
            }
            if ($reviewRatio >= 0.4) {
                $flags[] = 'ratio_verification_eleve';
            }
            if ($total >= 8) {
                $score -= min(20.0, ($total - 7) * 2.5);
                $flags[] = 'volume_reclamations_eleve';
            }
        }

        if (mb_strtolower((string) $user->getStatut()) !== 'actif') {
            $score -= 15.0;
            $flags[] = 'compte_inactif';
        }

        $role = (string) $user->getRole();
        if ($role === 'ROLE_ADMIN') {
            $score += 3.0;
        }

        $score = round(max(0.0, min(100.0, $score)), 2);

        return [
            'score' => $score,
            'level' => $this->mapLevel($score),
            'flags' => array_values(array_unique($flags)),
        ];
    }

    private function mapLevel(float $score): string
    {
        if ($score >= 75.0) {
            return 'Élevé';
        }

        if ($score >= 45.0) {
            return 'Moyen';
        }

        return 'Faible';
    }
}
