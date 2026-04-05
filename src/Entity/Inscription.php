<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ORM\Table(name: 'inscriptions')]
#[ORM\UniqueConstraint(name: 'unique_inscription', columns: ['session_id', 'id_formation'])]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionId = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(name: 'id_formation', referencedColumnName: 'id_formation', nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomParticipant = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $emailParticipant = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getSessionId(): ?string { return $this->sessionId; }
    public function setSessionId(string $sessionId): static { $this->sessionId = $sessionId; return $this; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): static { $this->formation = $formation; return $this; }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    public function getNomParticipant(): ?string { return $this->nomParticipant; }
    public function setNomParticipant(?string $nomParticipant): static { $this->nomParticipant = $nomParticipant; return $this; }

    public function getEmailParticipant(): ?string { return $this->emailParticipant; }
    public function setEmailParticipant(?string $emailParticipant): static { $this->emailParticipant = $emailParticipant; return $this; }
}
