<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FormationRepository;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_formation = null;

    public function getId_formation(): ?int
    {
        return $this->id_formation;
    }

    public function setId_formation(int $id_formation): self
    {
        $this->id_formation = $id_formation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
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

    #[ORM\ManyToOne(targetEntity: CategoriesFormation::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie')]
    private ?CategoriesFormation $categoriesFormation = null;

    public function getCategoriesFormation(): ?CategoriesFormation
    {
        return $this->categoriesFormation;
    }

    public function setCategoriesFormation(?CategoriesFormation $categoriesFormation): self
    {
        $this->categoriesFormation = $categoriesFormation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duree_heures = null;

    public function getDuree_heures(): ?int
    {
        return $this->duree_heures;
    }

    public function setDuree_heures(?int $duree_heures): self
    {
        $this->duree_heures = $duree_heures;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $formateur = null;

    public function getFormateur(): ?string
    {
        return $this->formateur;
    }

    public function setFormateur(?string $formateur): self
    {
        $this->formateur = $formateur;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $capacite_max = null;

    public function getCapacite_max(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapacite_max(int $capacite_max): self
    {
        $this->capacite_max = $capacite_max;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $place_disponible = null;

    public function getPlace_disponible(): ?int
    {
        return $this->place_disponible;
    }

    public function setPlace_disponible(?int $place_disponible): self
    {
        $this->place_disponible = $place_disponible;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_debut = null;

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(?\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_fin = null;

    public function getDate_fin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDate_fin(?\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ContenuModule::class, mappedBy: 'formation')]
    private Collection $contenuModules;

    /**
     * @return Collection<int, ContenuModule>
     */
    public function getContenuModules(): Collection
    {
        if (!$this->contenuModules instanceof Collection) {
            $this->contenuModules = new ArrayCollection();
        }
        return $this->contenuModules;
    }

    public function addContenuModule(ContenuModule $contenuModule): self
    {
        if (!$this->getContenuModules()->contains($contenuModule)) {
            $this->getContenuModules()->add($contenuModule);
        }
        return $this;
    }

    public function removeContenuModule(ContenuModule $contenuModule): self
    {
        $this->getContenuModules()->removeElement($contenuModule);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: InscriptionsFormation::class, mappedBy: 'formation')]
    private ?InscriptionsFormation $inscriptionsFormation = null;

    public function __construct()
    {
        $this->contenuModules = new ArrayCollection();
    }

    public function getInscriptionsFormation(): ?InscriptionsFormation
    {
        return $this->inscriptionsFormation;
    }

    public function setInscriptionsFormation(?InscriptionsFormation $inscriptionsFormation): self
    {
        $this->inscriptionsFormation = $inscriptionsFormation;
        return $this;
    }

    public function getIdFormation(): ?int
    {
        return $this->id_formation;
    }

    public function getDureeHeures(): ?int
    {
        return $this->duree_heures;
    }

    public function setDureeHeures(?int $duree_heures): static
    {
        $this->duree_heures = $duree_heures;

        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapaciteMax(int $capacite_max): static
    {
        $this->capacite_max = $capacite_max;

        return $this;
    }

    public function getPlaceDisponible(): ?int
    {
        return $this->place_disponible;
    }

    public function setPlaceDisponible(?int $place_disponible): static
    {
        $this->place_disponible = $place_disponible;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(?\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

}
