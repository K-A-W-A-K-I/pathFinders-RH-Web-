<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
#[ORM\HasLifecycleCallbacks]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_evenement', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CategorieEvenement::class)]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie_evenement', nullable: true)]
    #[Assert\NotNull(message: 'La categorie est obligatoire.')]
    private ?CategorieEvenement $categorie = null;

    #[ORM\Column(name: 'titre', type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le titre ne peut pas depasser 100 caracteres.')]
    private ?string $titre = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'La description ne peut pas depasser 2000 caracteres.')]
    private ?string $description = null;

    #[ORM\Column(name: 'image_path', type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Le chemin de l image ne peut pas depasser 500 caracteres.')]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de debut est obligatoire.')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'lieu', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Le lieu ne peut pas depasser 100 caracteres.')]
    private ?string $lieu = null;

    #[ORM\Column(name: 'capacite_max', type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'La capacite maximale est obligatoire.')]
    #[Assert\Positive(message: 'La capacite doit etre un nombre positif.')]
    private ?int $capaciteMax = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: ['Actif', 'Complet', 'Annulé', 'Terminé'], message: 'Statut invalide.')]
    private ?string $statut = 'Actif';

    #[ORM\Column(name: 'type_evenement', type: Types::STRING, length: 30)]
    #[Assert\NotBlank(message: 'Le type d evenement est obligatoire.')]
    #[Assert\Choice(choices: ['Professionnel', 'Non professionnel'], message: 'Type invalide.')]
    private ?string $typeEvenement = null;

    #[ORM\Column(name: 'prix', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    #[Assert\PositiveOrZero(message: 'Le prix doit etre positif ou nul.')]
    private ?string $prix = '0.00';

    #[ORM\Column(name: 'cree_par', type: Types::INTEGER, nullable: true)]
    private ?int $creePar = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'places_reservees', type: Types::INTEGER, options: ['default' => 0])]
    private int $placesReservees = 0;

    #[ORM\Column(name: 'places_restantes', type: Types::INTEGER, nullable: true)]
    private ?int $placesRestantes = null;

    #[Assert\Callback]
    public function validateBusinessRules(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($this->dateDebut && $this->dateFin && $this->dateFin < $this->dateDebut) {
            $context->buildViolation('La date de fin doit etre apres ou egale a la date de debut.')
                ->atPath('dateFin')
                ->addViolation();
        }

        if ($this->capaciteMax !== null && $this->capaciteMax < $this->placesReservees) {
            $context->buildViolation('La capacite maximale doit etre superieure ou egale aux places reservees.')
                ->atPath('capaciteMax')
                ->addViolation();
        }
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->dateCreation === null) {
            $this->dateCreation = new \DateTime();
        }
        $this->updatePlacesRestantes();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatePlacesRestantes();
    }

    private function updatePlacesRestantes(): void
    {
        if ($this->capaciteMax !== null) {
            $this->placesRestantes = $this->capaciteMax - $this->placesReservees;
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getCategorie(): ?CategorieEvenement { return $this->categorie; }
    public function setCategorie(?CategorieEvenement $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): static { $this->imagePath = $imagePath; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): static { $this->lieu = $lieu; return $this; }

    public function getCapaciteMax(): ?int { return $this->capaciteMax; }
    public function setCapaciteMax(int $capaciteMax): static { $this->capaciteMax = $capaciteMax; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getTypeEvenement(): ?string { return $this->typeEvenement; }
    public function setTypeEvenement(string $typeEvenement): static { $this->typeEvenement = $typeEvenement; return $this; }

    public function getPrix(): ?string { return $this->prix; }
    public function setPrix(?string $prix): static { $this->prix = $prix; return $this; }

    public function getCreePar(): ?int { return $this->creePar; }
    public function setCreePar(?int $creePar): static { $this->creePar = $creePar; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getPlacesReservees(): int { return $this->placesReservees; }
    public function setPlacesReservees(int $placesReservees): static { $this->placesReservees = $placesReservees; return $this; }

    public function getPlacesRestantes(): ?int { return $this->placesRestantes; }
    public function setPlacesRestantes(?int $placesRestantes): static { $this->placesRestantes = $placesRestantes; return $this; }

    public function getPlacesRestantesCalculated(): int
    {
        if ($this->placesRestantes !== null) {
            return $this->placesRestantes;
        }

        return max(0, ($this->capaciteMax ?? 0) - $this->placesReservees);
    }

    public function isFull(): bool
    {
        return $this->getPlacesRestantesCalculated() <= 0;
    }

    public function getPlacesPercentage(): float
    {
        if (!$this->capaciteMax) {
            return 100;
        }

        return ($this->getPlacesRestantesCalculated() / $this->capaciteMax) * 100;
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statut) {
            'Actif' => 'badge-actif',
            'Complet' => 'badge-complet',
            'Annulé' => 'badge-annule',
            'Terminé' => 'badge-termine',
            default => 'badge-default',
        };
    }

    public function getDureeJours(): int
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return 0;
        }

        return $this->dateDebut->diff($this->dateFin)->days + 1;
    }
}
