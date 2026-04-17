<?php

namespace App\Chart;

use Mukadi\Chart\ChartDefinitionBuilderInterface;
use Mukadi\Chart\ChartDefinitionInterface;

class StatutFormationsChart implements ChartDefinitionInterface
{
    public function define(ChartDefinitionBuilderInterface $builder): void
    {
        // This chart uses pre-computed data passed via setOptions
        $builder
            ->asDoughnut()
            ->query("SELECT 1 AS val FROM App\Entity\InscriptionsFormation i WHERE 1=0")
            ->labels(['Terminées', 'En cours', 'Non débutées'])
            ->dataset('Statut')
                ->data('val')
                ->useRandomBackgroundColor()
            ->end()
        ;
    }
}
