<?php

namespace App\Chart;

use Mukadi\Chart\ChartDefinitionBuilderInterface;
use Mukadi\Chart\ChartDefinitionInterface;

class ProgressionFormationsChart implements ChartDefinitionInterface
{
    public function define(ChartDefinitionBuilderInterface $builder): void
    {
        $dql = "SELECT f.titre AS titre, i.pourcentage_progression AS progression
                FROM App\Entity\InscriptionsFormation i
                JOIN i.formation f
                JOIN i.utilisateur u
                WHERE u.id = :userId";

        $builder
            ->asBar()
            ->query($dql)
            ->labels('titre')
            ->dataset('Progression (%)')
                ->data('progression')
                ->useRandomBackgroundColor()
            ->end()
        ;
    }
}
