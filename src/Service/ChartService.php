<?php

namespace App\Service;

class ChartService
{
    private const BASE_URL = 'https://quickchart.io/chart';
    private const WIDTH    = 700;
    private const HEIGHT   = 420;

    /**
     * Build a QuickChart URL for candidature score distribution.
     * @param array $candidatures  Candidature[]
     */
    public function buildScoreDistributionUrl(array $candidatures): string
    {
        // Buckets: 0-20, 21-40, 41-60, 61-80, 81-100
        $buckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
        foreach ($candidatures as $c) {
            $s = $c->getScore();
            if ($s <= 20)      $buckets['0-20']++;
            elseif ($s <= 40)  $buckets['21-40']++;
            elseif ($s <= 60)  $buckets['41-60']++;
            elseif ($s <= 80)  $buckets['61-80']++;
            else               $buckets['81-100']++;
        }

        $config = [
            'type' => 'bar',
            'data' => [
                'labels'   => array_keys($buckets),
                'datasets' => [[
                    'label'           => 'Candidats',
                    'data'            => array_values($buckets),
                    'backgroundColor' => ['#6C63FF','#9D6BFF','#4ade80','#facc15','#f87171'],
                    'borderRadius'    => 6,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
            ],
        ];

        return self::BASE_URL . '?w=' . self::WIDTH . '&h=' . self::HEIGHT
            . '&c=' . urlencode(json_encode($config));
    }

    /**
     * Build a doughnut chart URL for statut distribution.
     * @param array $candidatures  Candidature[]
     */
    public function buildStatutChartUrl(array $candidatures): string
    {
        $accepte = 0; $refuse = 0; $attente = 0;
        foreach ($candidatures as $c) {
            match ($c->getStatutAdmin()) {
                1  => $accepte++,
                -1 => $refuse++,
                default => $attente++,
            };
        }

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels'   => ['Acceptés', 'Refusés', 'En attente'],
                'datasets' => [[
                    'data'            => [$accepte, $refuse, $attente],
                    'backgroundColor' => ['#4ade80', '#f87171', '#facc15'],
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['position' => 'bottom']],
            ],
        ];

        return self::BASE_URL . '?w=400&h=300&c=' . urlencode(json_encode($config));
    }
}
