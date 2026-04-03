<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CategorieEvenementRepository;

#[ORM\Entity(repositoryClass: CategorieEvenementRepository::class)]
#[ORM\Table(name: 'categorie_evenement')]
class CategorieEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_categorie_evenement = null;

    public function getId_categorie_evenement(): ?int
    {
        return $this->id_categorie_evenement;
    }

    public function setId_categorie_evenement(int $id_categorie_evenement): self
    {
        $this->id_categorie_evenement = $id_categorie_evenement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_categorie = null;

    public function getNom_categorie(): ?string
    {
        return $this->nom_categorie;
    }

    public function setNom_categorie(string $nom_categorie): self
    {
        $this->nom_categorie = $nom_categorie;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'categorieEvenement')]
    private Collection $evenements;

    public function __construct()
    {
        $this->evenements = new ArrayCollection();
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        if (!$this->evenements instanceof Collection) {
            $this->evenements = new ArrayCollection();
        }
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        if (!$this->getEvenements()->contains($evenement)) {
            $this->getEvenements()->add($evenement);
        }
        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        $this->getEvenements()->removeElement($evenement);
        return $this;
    }

    public function getIdCategorieEvenement(): ?int
    {
        return $this->id_categorie_evenement;
    }

    public function getNomCategorie(): ?string
    {
        return $this->nom_categorie;
    }

    public function setNomCategorie(string $nom_categorie): static
    {
        $this->nom_categorie = $nom_categorie;

        return $this;
    }

}
