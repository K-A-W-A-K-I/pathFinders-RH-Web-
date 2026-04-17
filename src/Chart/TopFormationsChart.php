<?php

namespace App\Chart;

use Mukadi\Chart\ChartDefinitionBuilderInterface;
use Mukadi\Chart\ChartDefinitionInterface;

class TopFormationsChart implements ChartDefinitionInterface
{
    public function define(ChartDefinitionBuilderInterface $builder): void
    {
        $dql = "SELECT f.titre AS titre, COUNT(i.id_inscription) AS nbInscrits
                FROM App\Entity\InscriptionsFormation i
                JOIN i.formation f
                GROUP BY f.idFormation
                ORDER BY nbInscrits DESC";

        $builder
            ->asBar()
            ->query($dql)
            ->labels('titre')
            ->dataset('Inscriptions')
                ->data('nbInscrits')
                ->useRandomBackgroundColor()
            ->end()
        ;
    }
}
