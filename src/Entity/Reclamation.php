<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamation')]
#[ORM\HasLifecycleCallbacks]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reclamation')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_utilisateur')]
    private int $idUtilisateur;

    #[ORM\Column(length: 255)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'En attente'])]
    private ?string $statut = 'En attente';

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(name: 'date_modification', type: 'datetime')]
    private \DateTimeInterface $dateModification;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreation    = new \DateTime();
        $this->dateModification = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateModification = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $v): static { $this->idUtilisateur = $v; return $this; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $v): static { $this->titre = $v; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $v): static { $this->description = $v; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $v): static { $this->statut = $v; return $this; }
    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function getDateModification(): \DateTimeInterface { return $this->dateModification; }
}
