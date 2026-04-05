<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_formation')]
    private ?int $idFormation = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: false)]
    private ?CategorieFormation $categorie = null;

    #[ORM\Column(name: 'duree_heures', type: Types::DECIMAL, precision: 5, scale: 1, nullable: true)]
    private ?string $dureeHeures = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $formateur = null;

    #[ORM\Column(name: 'capacite_max', type: 'integer')]
    private ?int $capaciteMax = null;

    #[ORM\Column(name: 'place_disponible', type: 'integer')]
    private ?int $placeDisponible = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: ContenuModule::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $contenuModules;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Inscription::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $inscriptions;

    public function __construct()
    {
        $this->contenuModules = new ArrayCollection();
        $this->inscriptions   = new ArrayCollection();
    }

    public function getIdFormation(): ?int { return $this->idFormation; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCategorie(): ?CategorieFormation { return $this->categorie; }
    public function setCategorie(?CategorieFormation $categorie): static { $this->categorie = $categorie; return $this; }

    public function getDureeHeures(): ?string { return $this->dureeHeures; }
    public function setDureeHeures(?string $dureeHeures): static { $this->dureeHeures = $dureeHeures; return $this; }

    public function getFormateur(): ?string { return $this->formateur; }
    public function setFormateur(?string $formateur): static { $this->formateur = $formateur; return $this; }

    public function getCapaciteMax(): ?int { return $this->capaciteMax; }
    public function setCapaciteMax(int $capaciteMax): static { $this->capaciteMax = $capaciteMax; return $this; }

    public function getPlaceDisponible(): ?int { return $this->placeDisponible; }
    public function setPlaceDisponible(int $placeDisponible): static { $this->placeDisponible = $placeDisponible; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getContenuModules(): Collection { return $this->contenuModules; }

    public function addContenuModule(ContenuModule $contenuModule): static
    {
        if (!$this->contenuModules->contains($contenuModule)) {
            $this->contenuModules->add($contenuModule);
            $contenuModule->setFormation($this);
        }
        return $this;
    }

    public function removeContenuModule(ContenuModule $contenuModule): static
    {
        if ($this->contenuModules->removeElement($contenuModule)) {
            if ($contenuModule->getFormation() === $this) {
                $contenuModule->setFormation(null);
            }
        }
        return $this;
    }

    public function getInscriptions(): Collection { return $this->inscriptions; }
}
