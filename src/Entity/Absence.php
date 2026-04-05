<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AbsenceRepository;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
#[ORM\Table(name: 'absence')]
class Absence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idAbsence = null;

    public function getIdAbsence(): ?int
    {
        return $this->idAbsence;
    }

    public function setIdAbsence(int $idAbsence): self
    {
        $this->idAbsence = $idAbsence;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'absences')]
    #[ORM\JoinColumn(name: 'employe', referencedColumnName: 'id_employee')]
    private ?Employee $employee = null;

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateAbsence = null;

    public function getDateAbsence(): ?\DateTimeInterface
    {
        return $this->dateAbsence;
    }

    public function setDateAbsence(\DateTimeInterface $dateAbsence): self
    {
        $this->dateAbsence = $dateAbsence;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $typeAbsence = null;

    public function getTypeAbsence(): ?string
    {
        return $this->typeAbsence;
    }

    public function setTypeAbsence(string $typeAbsence): self
    {
        $this->typeAbsence = $typeAbsence;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $justification = null;

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): self
    {
        $this->justification = $justification;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $impactPaie = null;

    public function isImpactPaie(): ?bool
    {
        return $this->impactPaie;
    }

    public function setImpactPaie(?bool $impactPaie): self
    {
        $this->impactPaie = $impactPaie;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'absences')]
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
