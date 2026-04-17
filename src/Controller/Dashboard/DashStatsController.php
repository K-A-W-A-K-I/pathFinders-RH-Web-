<?php

namespace App\Controller\Dashboard;

use App\Repository\CategorieFormationRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/stats', name: 'dashboard_stats_')]
class DashStatsController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        CategorieFormationRepository $catRepo,
        FormationRepository $formRepo,
        InscriptionRepository $inscRepo
    ): Response {
        $categories = $catRepo->findAll();
        $formations = $formRepo->findAll();
        $inscriptions = $inscRepo->findAll();

        // === DONNÉES POUR GRAPHIQUES ===

        // 1. Histogramme : Formations par catégorie
        $formationsByCategory = [];
        foreach ($categories as $cat) {
            $formationsByCategory[$cat->getNomCategorie()] = count($cat->getFormations());
        }

        // 2. Histogramme : Taux de remplissage par formation
        $fillRates = [];
        $formationNames = [];
        foreach ($formations as $f) {
            $formationNames[] = substr($f->getTitre(), 0, 20);
            $rate = $f->getCapaciteMax() > 0 
                ? round((($f->getCapaciteMax() - $f->getPlaceDisponible()) / $f->getCapaciteMax()) * 100, 1)
                : 0;
            $fillRates[] = $rate;
        }

        // 3. Courbe : Inscriptions par catégorie (cumul)
        $inscriptionsByCategory = [];
        foreach ($categories as $cat) {
            $count = 0;
            foreach ($cat->getFormations() as $f) {
                $count += count($f->getInscriptions());
            }
            $inscriptionsByCategory[$cat->getNomCategorie()] = $count;
        }

        // 4. Segmentation : Formations par taux de remplissage
        $segmentation = ['Vides' => 0, 'Partielles' => 0, 'Pleines' => 0];
        foreach ($formations as $f) {
            $rate = $f->getCapaciteMax() > 0 
                ? (($f->getCapaciteMax() - $f->getPlaceDisponible()) / $f->getCapaciteMax()) * 100
                : 0;
            if ($rate === 0) $segmentation['Vides']++;
            elseif ($rate < 100) $segmentation['Partielles']++;
            else $segmentation['Pleines']++;
        }

        // 5. Corrélation : Capacité vs Inscriptions
        $correlationData = [];
        foreach ($formations as $f) {
            $correlationData[] = [
                'x' => $f->getCapaciteMax(),
                'y' => count($f->getInscriptions()),
                'label' => substr($f->getTitre(), 0, 15),
            ];
        }

        // 6. Stats générales
        $totalInscriptions = count($inscriptions);
        $avgFillRate = count($formations) > 0 
            ? round(array_sum($fillRates) / count($fillRates), 1)
            : 0;
        $mostPopularFormation = null;
        $maxInscriptions = 0;
        foreach ($formations as $f) {
            $inscCount = count($f->getInscriptions());
            if ($inscCount > $maxInscriptions) {
                $maxInscriptions = $inscCount;
                $mostPopularFormation = $f->getTitre();
            }
        }

        return $this->render('dashboard/stats/index.html.twig', [
            'formationsByCategory'   => $formationsByCategory,
            'fillRates'              => $fillRates,
            'formationNames'         => $formationNames,
            'inscriptionsByCategory' => $inscriptionsByCategory,
            'segmentation'           => $segmentation,
            'correlationData'        => $correlationData,
            'totalInscriptions'      => $totalInscriptions,
            'avgFillRate'            => $avgFillRate,
            'mostPopularFormation'   => $mostPopularFormation,
            'totalFormations'        => count($formations),
            'totalCategories'        => count($categories),
        ]);
    }
}
