<?php

namespace App\Entity;

use App\Repository\CategorieFormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieFormationRepository::class)]
#[ORM\Table(name: 'categories_formation')]
class CategorieFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_categorie')]
    private ?int $idCategorie = null;

    #[ORM\Column(name: 'nom_categorie', length: 150)]
    private ?string $nomCategorie = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Formation::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $formations;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
    }

    public function getIdCategorie(): ?int { return $this->idCategorie; }

    public function getNomCategorie(): ?string { return $this->nomCategorie; }
    public function setNomCategorie(string $nomCategorie): static { $this->nomCategorie = $nomCategorie; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getFormations(): Collection { return $this->formations; }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setCategorie($this);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getCategorie() === $this) {
                $formation->setCategorie(null);
            }
        }
        return $this;
    }
}
