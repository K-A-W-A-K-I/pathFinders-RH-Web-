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
     * @param Candidat[] $candidats
     */
    public function hydrateNames(array $candidats): void
    {
        if (empty($candidats)) return;

        $ids = array_unique(array_map(fn($c) => $c->getIdUtilisateur(), $candidats));
        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $conn->fetchAllAssociative(
            "SELECT id_utilisateur, nom, prenom, email FROM utilisateurs WHERE id_utilisateur IN ($placeholders)",
            array_values($ids)
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row['id_utilisateur']] = $row;
        }

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
