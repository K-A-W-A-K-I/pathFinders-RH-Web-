<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PlanningRepository;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\Table(name: 'planning')]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idPlanning = null;

    public function getIdPlanning(): ?int
    {
        return $this->idPlanning;
    }

    public function setIdPlanning(int $idPlanning): self
    {
        $this->idPlanning = $idPlanning;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $effectifPrevu = null;

    public function getEffectifPrevu(): ?int
    {
        return $this->effectifPrevu;
    }

    public function setEffectifPrevu(int $effectifPrevu): self
    {
        $this->effectifPrevu = $effectifPrevu;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $effectifPresent = null;

    public function getEffectifPresent(): ?int
    {
        return $this->effectifPresent;
    }

    public function setEffectifPresent(int $effectifPresent): self
    {
        $this->effectifPresent = $effectifPresent;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $remarque = null;

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(?string $remarque): self
    {
        $this->remarque = $remarque;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'plannings')]
    #[ORM\JoinColumn(name: 'creePar', referencedColumnName: 'id_utilisateur')]
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

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateCreation = null;

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

}
