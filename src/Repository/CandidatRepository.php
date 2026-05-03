<?php

namespace App\Repository;

use App\Entity\Candidat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CandidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidat::class);
    }

    public function findByUserId(int $userId): ?Candidat
    {
        return $this->findOneBy(['idUtilisateur' => $userId]);
    }

    /**
     * Hydrate nom/prenom/email on Candidat objects from the utilisateurs table.
     * OPTIMISATION N+1: Utilise une seule requête batch pour charger tous les utilisateurs.
     * Au lieu de faire N requêtes (une par candidat), fait 1 seule requête avec IN().
     * 
     * @param Candidat[] $candidats
     * @return void
     */
    public function hydrateNames(array $candidats): void
    {
        if (empty($candidats)) {
            return;
        }

        // Récupère tous les IDs utilisateurs
        $ids = array_unique(array_map(fn($c) => $c->getIdUtilisateur(), $candidats));
        
        // OPTIMISATION: Une seule requête SQL pour tous les utilisateurs
        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $conn->fetchAllAssociative(
            "SELECT id_utilisateur, nom, prenom, email FROM utilisateurs WHERE id_utilisateur IN ($placeholders)",
            array_values($ids)
        );

        // Créer un map pour accès rapide
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id_utilisateur']] = $row;
        }

        // Hydrater tous les candidats
        foreach ($candidats as $c) {
            $data = $map[$c->getIdUtilisateur()] ?? null;
            if ($data) {
                $c->setNom($data['nom']);
                $c->setPrenom($data['prenom']);
                $c->setEmail($data['email']);
            }
        }
    }
}
