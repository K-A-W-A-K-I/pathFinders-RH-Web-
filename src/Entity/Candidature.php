<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CandidatureRepository;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ORM\Table(name: 'candidatures')]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_candidature = null;

    public function getId_candidature(): ?int
    {
        return $this->id_candidature;
    }

    public function setId_candidature(int $id_candidature): self
    {
        $this->id_candidature = $id_candidature;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id_offre')]
    private ?Offre $offre = null;

    public function getOffre(): ?Offre
    {
        return $this->offre;
    }

    public function setOffre(?Offre $offre): self
    {
        $this->offre = $offre;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Candidat::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'id_candidat', referencedColumnName: 'id_candidat')]
    private ?Candidat $candidat = null;

    public function getCandidat(): ?Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(?Candidat $candidat): self
    {
        $this->candidat = $candidat;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $score = null;

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $admis = null;

    public function isAdmis(): ?bool
    {
        return $this->admis;
    }

    public function setAdmis(?bool $admis): self
    {
        $this->admis = $admis;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_passage = null;

    public function getDate_passage(): ?\DateTimeInterface
    {
        return $this->date_passage;
    }

    public function setDate_passage(\DateTimeInterface $date_passage): self
    {
        $this->date_passage = $date_passage;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $statut_admin = null;

    public function getStatut_admin(): ?int
    {
        return $this->statut_admin;
    }

    public function setStatut_admin(?int $statut_admin): self
    {
        $this->statut_admin = $statut_admin;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'candidature')]
    private Collection $entretiens;

    public function __construct()
    {
        $this->entretiens = new ArrayCollection();
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        if (!$this->entretiens instanceof Collection) {
            $this->entretiens = new ArrayCollection();
        }
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->getEntretiens()->contains($entretien)) {
            $this->getEntretiens()->add($entretien);
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        $this->getEntretiens()->removeElement($entretien);
        return $this;
    }

    public function getIdCandidature(): ?int
    {
        return $this->id_candidature;
    }

    public function getDatePassage(): ?\DateTime
    {
        return $this->date_passage;
    }

    public function setDatePassage(\DateTime $date_passage): static
    {
        $this->date_passage = $date_passage;

        return $this;
    }

    public function getStatutAdmin(): ?int
    {
        return $this->statut_admin;
    }

    public function setStatutAdmin(?int $statut_admin): static
    {
        $this->statut_admin = $statut_admin;

        return $this;
    }

}
