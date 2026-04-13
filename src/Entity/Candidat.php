<?php

namespace App\Entity;

use App\Repository\CandidatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidats')]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_candidat')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_utilisateur')]
    private int $idUtilisateur;

    #[ORM\Column(name: 'cv_path', type: 'text', nullable: true)]
    private ?string $cvPath = null;

    #[ORM\Column(name: 'lettre_motivation', type: 'text', nullable: true)]
    private ?string $lettreMotivation = null;

    #[ORM\Column(name: 'cv_score_ia', nullable: true)]
    private ?int $cvScoreIa = null;

    #[ORM\Column(name: 'cv_score_details', type: 'text', nullable: true)]
    private ?string $cvScoreDetails = null;

    #[ORM\Column(name: 'cv_analyse_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cvAnalyseDate = null;

    // Joined from utilisateurs (not mapped, loaded manually)
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $email = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Candidature::class)]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }
    public function getCvPath(): ?string { return $this->cvPath; }
    public function setCvPath(?string $cvPath): static { $this->cvPath = $cvPath; return $this; }
    public function getLettreMotivation(): ?string { return $this->lettreMotivation; }
    public function setLettreMotivation(?string $lettreMotivation): static { $this->lettreMotivation = $lettreMotivation; return $this; }
    public function getCvScoreIa(): ?int { return $this->cvScoreIa; }
    public function setCvScoreIa(?int $cvScoreIa): static { $this->cvScoreIa = $cvScoreIa; return $this; }
    public function getCvScoreDetails(): ?string { return $this->cvScoreDetails; }
    public function setCvScoreDetails(?string $cvScoreDetails): static { $this->cvScoreDetails = $cvScoreDetails; return $this; }
    public function getCvAnalyseDate(): ?\DateTimeInterface { return $this->cvAnalyseDate; }
    public function setCvAnalyseDate(?\DateTimeInterface $cvAnalyseDate): static { $this->cvAnalyseDate = $cvAnalyseDate; return $this; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getFullName(): string { return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')); }
    public function getCandidatures(): Collection { return $this->candidatures; }
}
