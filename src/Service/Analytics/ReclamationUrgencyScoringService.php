<?php

namespace App\Service\Analytics;

use App\Entity\Reclamation;

class ReclamationUrgencyScoringService
{
    private const SLA_HOURS = 24;

    /**
     * @param int $openAgeHours
     * @return array{score:float,priority:string,reasons:array<int,string>}
     */
    public function score(Reclamation $reclamation, int $openAgeHours): array
    {
        $score = 0.0;
        $reasons = [];

        $moderationScore = max(0.0, min(1.0, (float) ($reclamation->getModerationScore() ?? 0.0)));
        $score += $moderationScore * 35.0;

        $urgency = strtolower((string) $reclamation->getModerationUrgency());
        if ($urgency === 'high') {
            $score += 22.0;
            $reasons[] = 'urgence_ia_elevee';
        } elseif ($urgency === 'medium') {
            $score += 10.0;
        }

        $sentiment = strtolower((string) $reclamation->getModerationSentiment());
        if ($sentiment === 'negative') {
            $score += 14.0;
            $reasons[] = 'sentiment_negatif';
        } elseif ($sentiment === 'mixed') {
            $score += 6.0;
        }

        $status = mb_strtolower((string) $reclamation->getStatut());
        if (in_array($status, ['en attente', 'en cours'], true)) {
            $score += 6.0;

            if ($openAgeHours > self::SLA_HOURS) {
                $overSla = $openAgeHours - self::SLA_HOURS;
                $score += min(28.0, $overSla * 1.5);
                $reasons[] = 'depassement_sla';
            }
        }

        if ($reclamation->isManualReviewRequired()) {
            $score += 10.0;
            $reasons[] = 'revue_manuelle';
        }

        $score = round(max(0.0, min(100.0, $score)), 2);

        return [
            'score' => $score,
            'priority' => $this->mapPriority($score),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function mapPriority(float $score): string
    {
        if ($score >= 80.0) {
            return 'Critique';
        }

        if ($score >= 60.0) {
            return 'Haute';
        }

        if ($score >= 40.0) {
            return 'Moyenne';
        }

        return 'Basse';
    }
}
