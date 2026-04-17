<?php

namespace App\Entity;

use App\Repository\EntretienRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
#[ORM\Table(name: 'entretiens')]
class Entretien
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_CONFIRME   = 'CONFIRME';
    public const STATUT_REFUSE     = 'REFUSE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_entretien')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Candidature::class, inversedBy: 'entretiens')]
    #[ORM\JoinColumn(name: 'id_candidature', referencedColumnName: 'id_candidature', nullable: false)]
    private ?Candidature $candidature = null;

    #[ORM\ManyToOne(targetEntity: Offre::class)]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id_offre', nullable: false)]
    private ?Offre $offre = null;

    #[ORM\ManyToOne(targetEntity: Candidat::class)]
    #[ORM\JoinColumn(name: 'id_candidat', referencedColumnName: 'id_candidat', nullable: false)]
    private ?Candidat $candidat = null;

    #[ORM\Column(name: 'date_entretien', type: 'datetime')]
    private ?\DateTimeInterface $dateEntretien = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'interview_token', length: 64, nullable: true, unique: true)]
    private ?string $interviewToken = null;

    #[ORM\Column(name: 'interview_score', nullable: true)]
    private ?int $interviewScore = null;

    #[ORM\Column(name: 'interview_completed')]
    private bool $interviewCompleted = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getCandidature(): ?Candidature { return $this->candidature; }
    public function setCandidature(?Candidature $candidature): static { $this->candidature = $candidature; return $this; }
    public function getOffre(): ?Offre { return $this->offre; }
    public function setOffre(?Offre $offre): static { $this->offre = $offre; return $this; }
    public function getCandidat(): ?Candidat { return $this->candidat; }
    public function setCandidat(?Candidat $candidat): static { $this->candidat = $candidat; return $this; }
    public function getDateEntretien(): ?\DateTimeInterface { return $this->dateEntretien; }
    public function setDateEntretien(?\DateTimeInterface $dateEntretien): static { $this->dateEntretien = $dateEntretien; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getInterviewToken(): ?string { return $this->interviewToken; }
    public function setInterviewToken(?string $token): static { $this->interviewToken = $token; return $this; }

    public function getInterviewScore(): ?int { return $this->interviewScore; }
    public function setInterviewScore(?int $score): static { $this->interviewScore = $score; return $this; }

    public function isInterviewCompleted(): bool { return $this->interviewCompleted; }
    public function setInterviewCompleted(bool $completed): static { $this->interviewCompleted = $completed; return $this; }

    public function getStatutLabel(): string
    {
        return match($this->statut) {
            self::STATUT_CONFIRME => '✅ Confirmé',
            self::STATUT_REFUSE   => '❌ Refusé',
            default               => '⏳ En attente',
        };
    }

    public function getStatutClass(): string
    {
        return match($this->statut) {
            self::STATUT_CONFIRME => 'badge-confirme',
            self::STATUT_REFUSE   => 'badge-refuse',
            default               => 'badge-attente',
        };
    }
}
