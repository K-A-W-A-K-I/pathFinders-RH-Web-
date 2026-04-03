<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EvenementRepository;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_evenement = null;

    public function getId_evenement(): ?int
    {
        return $this->id_evenement;
    }

    public function setId_evenement(int $id_evenement): self
    {
        $this->id_evenement = $id_evenement;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: CategorieEvenement::class, inversedBy: 'evenements')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie_evenement')]
    private ?CategorieEvenement $categorieEvenement = null;

    public function getCategorieEvenement(): ?CategorieEvenement
    {
        return $this->categorieEvenement;
    }

    public function setCategorieEvenement(?CategorieEvenement $categorieEvenement): self
    {
        $this->categorieEvenement = $categorieEvenement;
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

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_debut = null;

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_fin = null;

    public function getDate_fin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDate_fin(\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $lieu = null;

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): self
    {
        $this->lieu = $lieu;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_evenement = null;

    public function getType_evenement(): ?string
    {
        return $this->type_evenement;
    }

    public function setType_evenement(string $type_evenement): self
    {
        $this->type_evenement = $type_evenement;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $prix = null;

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'evenements')]
    #[ORM\JoinColumn(name: 'cree_par', referencedColumnName: 'id_utilisateur')]
    private ?Utilisateur $utilisateur = null;

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_creation = null;

    public function getDate_creation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDate_creation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: InscriptionEvenement::class, mappedBy: 'evenement')]
    private ?InscriptionEvenement $inscriptionEvenement = null;

    public function getInscriptionEvenement(): ?InscriptionEvenement
    {
        return $this->inscriptionEvenement;
    }

    public function setInscriptionEvenement(?InscriptionEvenement $inscriptionEvenement): self
    {
        $this->inscriptionEvenement = $inscriptionEvenement;
        return $this;
    }

    public function getIdEvenement(): ?int
    {
        return $this->id_evenement;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

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

    public function getTypeEvenement(): ?string
    {
        return $this->type_evenement;
    }

    public function setTypeEvenement(string $type_evenement): static
    {
        $this->type_evenement = $type_evenement;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

}
