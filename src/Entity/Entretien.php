<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EntretienRepository;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
#[ORM\Table(name: 'entretiens')]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_entretien = null;

    public function getId_entretien(): ?int
    {
        return $this->id_entretien;
    }

    public function setId_entretien(int $id_entretien): self
    {
        $this->id_entretien = $id_entretien;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Candidature::class, inversedBy: 'entretiens')]
    #[ORM\JoinColumn(name: 'id_candidature', referencedColumnName: 'id_candidature')]
    private ?Candidature $candidature = null;

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(?Candidature $candidature): self
    {
        $this->candidature = $candidature;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_offre = null;

    public function getId_offre(): ?int
    {
        return $this->id_offre;
    }

    public function setId_offre(int $id_offre): self
    {
        $this->id_offre = $id_offre;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_candidat = null;

    public function getId_candidat(): ?int
    {
        return $this->id_candidat;
    }

    public function setId_candidat(int $id_candidat): self
    {
        $this->id_candidat = $id_candidat;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_entretien = null;

    public function getDate_entretien(): ?\DateTimeInterface
    {
        return $this->date_entretien;
    }

    public function setDate_entretien(\DateTimeInterface $date_entretien): self
    {
        $this->date_entretien = $date_entretien;
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getIdEntretien(): ?int
    {
        return $this->id_entretien;
    }

    public function getIdOffre(): ?int
    {
        return $this->id_offre;
    }

    public function setIdOffre(int $id_offre): static
    {
        $this->id_offre = $id_offre;

        return $this;
    }

    public function getIdCandidat(): ?int
    {
        return $this->id_candidat;
    }

    public function setIdCandidat(int $id_candidat): static
    {
        $this->id_candidat = $id_candidat;

        return $this;
    }

    public function getDateEntretien(): ?\DateTime
    {
        return $this->date_entretien;
    }

    public function setDateEntretien(\DateTime $date_entretien): static
    {
        $this->date_entretien = $date_entretien;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

}
