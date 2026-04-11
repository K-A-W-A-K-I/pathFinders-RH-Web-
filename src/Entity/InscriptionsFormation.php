<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\InscriptionsFormationRepository;

#[ORM\Entity(repositoryClass: InscriptionsFormationRepository::class)]
#[ORM\Table(name: 'inscriptions_formation')]
class InscriptionsFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_inscription = null;

    public function getId_inscription(): ?int
    {
        return $this->id_inscription;
    }

    public function setId_inscription(int $id_inscription): self
    {
        $this->id_inscription = $id_inscription;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: true)]
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

    #[ORM\ManyToOne(targetEntity: Formation::class)]
    #[ORM\JoinColumn(name: 'id_formation', referencedColumnName: 'id_formation', nullable: true, onDelete: 'SET NULL')]
    private ?Formation $formation = null;

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_inscription = null;

    public function getDate_inscription(): ?\DateTimeInterface
    {
        return $this->date_inscription;
    }

    public function setDate_inscription(\DateTimeInterface $date_inscription): self
    {
        $this->date_inscription = $date_inscription;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $pourcentage_progression = null;

    public function getPourcentage_progression(): ?string
    {
        return $this->pourcentage_progression;
    }

    public function setPourcentage_progression(?string $pourcentage_progression): self
    {
        $this->pourcentage_progression = $pourcentage_progression;
        return $this;
    }

    public function getIdInscription(): ?int
    {
        return $this->id_inscription;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->date_inscription;
    }

    public function setDateInscription(\DateTime $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    public function getPourcentageProgression(): ?string
    {
        return $this->pourcentage_progression;
    }

    public function setPourcentageProgression(?string $pourcentage_progression): static
    {
        $this->pourcentage_progression = $pourcentage_progression;

        return $this;
    }

}
