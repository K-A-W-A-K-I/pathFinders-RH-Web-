<?php

namespace App\Entity;

use App\Repository\CategorieEvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieEvenementRepository::class)]
#[ORM\Table(name: 'categorie_evenement')]
class CategorieEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_categorie_evenement', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_categorie', type: Types::STRING, length: 100)]
    private ?string $nomCategorie = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int { return $this->id; }

    public function getNomCategorie(): ?string { return $this->nomCategorie; }
    public function setNomCategorie(string $nomCategorie): static { $this->nomCategorie = $nomCategorie; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function __toString(): string { return $this->nomCategorie ?? ''; }
}
