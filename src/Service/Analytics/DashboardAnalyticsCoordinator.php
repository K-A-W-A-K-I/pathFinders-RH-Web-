<?php

namespace App\Service\Analytics;

use App\Entity\Reclamation;
use App\Entity\Utilisateur;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardAnalyticsCoordinator
{
    public function __construct(
        private readonly ReclamationRepository $reclamationRepository,
        private readonly ReclamationUrgencyScoringService $urgencyScoringService,
        private readonly ReclamationAnomalyDetectionService $anomalyDetectionService,
        private readonly UserTrustScoringService $userTrustScoringService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<int,Reclamation> $reclamations
     */
    public function refreshReclamationAnalytics(array $reclamations): void
    {
        if ($reclamations === []) {
            return;
        }

        $ids = [];
        $userIds = [];
        foreach ($reclamations as $reclamation) {
            $id = $reclamation->getId();
            if ($id !== null) {
                $ids[] = $id;
            }
            $userIds[] = $reclamation->getIdUtilisateur();
        }

        $ageById = $this->reclamationRepository->findOpenAgeHoursByIds($ids);
        $burstByUser = $this->reclamationRepository->countRecentByUserIds(array_values(array_unique($userIds)), 24);

        foreach ($reclamations as $reclamation) {
            $rid = (int) ($reclamation->getId() ?? 0);
            $uid = $reclamation->getIdUtilisateur();

            $urgency = $this->urgencyScoringService->score($reclamation, $ageById[$rid] ?? 0);
            $sameTitle = $this->reclamationRepository->countRecentByExactTitle($reclamation->getTitre(), 48, $rid > 0 ? $rid : null);
            $anomaly = $this->anomalyDetectionService->detect($reclamation, $sameTitle, $burstByUser[$uid] ?? 0);

            $reclamation->setAnalyticsUrgencyScore($urgency['score']);
            $reclamation->setAnalyticsPriorityLevel($urgency['priority']);
            $reclamation->setAnalyticsAnomalyScore($anomaly['score']);
            $reclamation->setAnalyticsAnomalyFlag($anomaly['flag']);
            $reclamation->setAnalyticsAnomalyReasons($anomaly['reasons']);
            $reclamation->setAnalyticsLastCalculatedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<int,Utilisateur> $users
     */
    public function refreshUserTrustAnalytics(array $users): void
    {
        if ($users === []) {
            return;
        }

        $userIds = [];
        foreach ($users as $user) {
            $id = $user->getId();
            if ($id !== null) {
                $userIds[] = $id;
            }
        }

        $statsByUser = $this->reclamationRepository->findUserReclamationStats(array_values(array_unique($userIds)), 90);

        foreach ($users as $user) {
            $uid = (int) ($user->getId() ?? 0);
            $stats = $statsByUser[$uid] ?? [
                'total' => 0,
                'open' => 0,
                'rejected' => 0,
                'review' => 0,
                'resolved' => 0,
                'repondu' => 0,
            ];

            $trust = $this->userTrustScoringService->score($user, $stats);

            $user->setAnalyticsTrustScore($trust['score']);
            $user->setAnalyticsTrustLevel($trust['level']);
            $user->setAnalyticsTrustFlags($trust['flags']);
            $user->setAnalyticsLastCalculatedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }
}
