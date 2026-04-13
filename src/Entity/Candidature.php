<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ORM\Table(name: 'candidatures')]
class Candidature
{
    public const STATUT_EN_ATTENTE = 0;
    public const STATUT_ACCEPTE    = 1;
    public const STATUT_REFUSE     = -1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_candidature')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id_offre', nullable: false)]
    private ?Offre $offre = null;

    #[ORM\ManyToOne(targetEntity: Candidat::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'id_candidat', referencedColumnName: 'id_candidat', nullable: false)]
    private ?Candidat $candidat = null;

    #[ORM\Column]
    private int $score = 0;

    #[ORM\Column]
    private bool $admis = false;

    #[ORM\Column(name: 'date_passage', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datePassage = null;

    #[ORM\Column(name: 'statut_admin')]
    private int $statutAdmin = 0;

    #[ORM\Column(name: 'cv_score_ia', nullable: true)]
    private ?int $cvScoreIa = null;

    #[ORM\OneToMany(mappedBy: 'candidature', targetEntity: Entretien::class, cascade: ['remove'])]
    private Collection $entretiens;

    public function __construct()
    {
        $this->entretiens = new ArrayCollection();
        $this->datePassage = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getOffre(): ?Offre { return $this->offre; }
    public function setOffre(?Offre $offre): static { $this->offre = $offre; return $this; }
    public function getCandidat(): ?Candidat { return $this->candidat; }
    public function setCandidat(?Candidat $candidat): static { $this->candidat = $candidat; return $this; }
    public function getScore(): int { return $this->score; }
    public function setScore(int $score): static { $this->score = $score; return $this; }
    public function isAdmis(): bool { return $this->admis; }
    public function setAdmis(bool $admis): static { $this->admis = $admis; return $this; }
    public function getDatePassage(): ?\DateTimeInterface { return $this->datePassage; }
    public function setDatePassage(?\DateTimeInterface $datePassage): static { $this->datePassage = $datePassage; return $this; }
    public function getStatutAdmin(): int { return $this->statutAdmin; }
    public function setStatutAdmin(int $statutAdmin): static { $this->statutAdmin = $statutAdmin; return $this; }
    public function getCvScoreIa(): ?int { return $this->cvScoreIa; }
    public function setCvScoreIa(?int $cvScoreIa): static { $this->cvScoreIa = $cvScoreIa; return $this; }
    public function getEntretiens(): Collection { return $this->entretiens; }

    public function getStatutLabel(): string
    {
        return match($this->statutAdmin) {
            self::STATUT_ACCEPTE => 'Accepté',
            self::STATUT_REFUSE  => 'Refusé',
            default              => 'En attente',
        };
    }

    public function getStatutClass(): string
    {
        return match($this->statutAdmin) {
            self::STATUT_ACCEPTE => 'badge-accepte',
            self::STATUT_REFUSE  => 'badge-refuse',
            default              => 'badge-attente',
        };
    }
}
