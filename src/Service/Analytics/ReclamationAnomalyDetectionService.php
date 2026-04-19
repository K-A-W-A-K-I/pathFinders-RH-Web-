<?php

namespace App\Service\Analytics;

use App\Entity\Reclamation;

class ReclamationAnomalyDetectionService
{
    /**
     * @param int $sameTitleRecentCount
     * @param int $userBurstCount
     * @return array{score:float,flag:bool,reasons:array<int,string>}
     */
    public function detect(Reclamation $reclamation, int $sameTitleRecentCount, int $userBurstCount): array
    {
        $score = 0.0;
        $reasons = [];

        if ($sameTitleRecentCount > 0) {
            $score += min(45.0, $sameTitleRecentCount * 18.0);
            $reasons[] = 'doublons_titre_recents';
        }

        if ($userBurstCount >= 3) {
            $score += min(35.0, ($userBurstCount - 2) * 10.0);
            $reasons[] = 'rafale_reclamations_utilisateur';
        }

        if ($reclamation->isManualReviewRequired()) {
            $score += 12.0;
            $reasons[] = 'revue_manuelle';
        }

        $decision = strtolower((string) $reclamation->getModerationDecision());
        if (in_array($decision, ['review', 'rejected'], true)) {
            $score += 12.0;
            $reasons[] = 'moderation_sensible';
        }

        if (mb_strlen(trim($reclamation->getDescription())) < 35) {
            $score += 6.0;
            $reasons[] = 'description_courte';
        }

        $score = round(max(0.0, min(100.0, $score)), 2);

        return [
            'score' => $score,
            'flag' => $score >= 60.0,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }
}
