<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CandidatRepository;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidats')]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'candidats')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur')]
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cv_path = null;

    public function getCv_path(): ?string
    {
        return $this->cv_path;
    }

    public function setCv_path(?string $cv_path): self
    {
        $this->cv_path = $cv_path;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lettre_motivation = null;

    public function getLettre_motivation(): ?string
    {
        return $this->lettre_motivation;
    }

    public function setLettre_motivation(?string $lettre_motivation): self
    {
        $this->lettre_motivation = $lettre_motivation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cv_score_ia = null;

    public function getCv_score_ia(): ?int
    {
        return $this->cv_score_ia;
    }

    public function setCv_score_ia(?int $cv_score_ia): self
    {
        $this->cv_score_ia = $cv_score_ia;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cv_score_details = null;

    public function getCv_score_details(): ?string
    {
        return $this->cv_score_details;
    }

    public function setCv_score_details(?string $cv_score_details): self
    {
        $this->cv_score_details = $cv_score_details;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cv_analyse_date = null;

    public function getCv_analyse_date(): ?\DateTimeInterface
    {
        return $this->cv_analyse_date;
    }

    public function setCv_analyse_date(?\DateTimeInterface $cv_analyse_date): self
    {
        $this->cv_analyse_date = $cv_analyse_date;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'candidat')]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        if (!$this->candidatures instanceof Collection) {
            $this->candidatures = new ArrayCollection();
        }
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): self
    {
        if (!$this->getCandidatures()->contains($candidature)) {
            $this->getCandidatures()->add($candidature);
        }
        return $this;
    }

    public function removeCandidature(Candidature $candidature): self
    {
        $this->getCandidatures()->removeElement($candidature);
        return $this;
    }

    public function getIdCandidat(): ?int
    {
        return $this->id_candidat;
    }

    public function getCvPath(): ?string
    {
        return $this->cv_path;
    }

    public function setCvPath(?string $cv_path): static
    {
        $this->cv_path = $cv_path;

        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettre_motivation;
    }

    public function setLettreMotivation(?string $lettre_motivation): static
    {
        $this->lettre_motivation = $lettre_motivation;

        return $this;
    }

    public function getCvScoreIa(): ?int
    {
        return $this->cv_score_ia;
    }

    public function setCvScoreIa(?int $cv_score_ia): static
    {
        $this->cv_score_ia = $cv_score_ia;

        return $this;
    }

    public function getCvScoreDetails(): ?string
    {
        return $this->cv_score_details;
    }

    public function setCvScoreDetails(?string $cv_score_details): static
    {
        $this->cv_score_details = $cv_score_details;

        return $this;
    }

    public function getCvAnalyseDate(): ?\DateTime
    {
        return $this->cv_analyse_date;
    }

    public function setCvAnalyseDate(?\DateTime $cv_analyse_date): static
    {
        $this->cv_analyse_date = $cv_analyse_date;

        return $this;
    }

}
