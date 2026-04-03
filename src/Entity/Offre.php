<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\OffreRepository;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\Table(name: 'offres')]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $domaine = null;

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(?string $domaine): self
    {
        $this->domaine = $domaine;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_contrat = null;

    public function getType_contrat(): ?string
    {
        return $this->type_contrat;
    }

    public function setType_contrat(?string $type_contrat): self
    {
        $this->type_contrat = $type_contrat;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $salaire_min = null;

    public function getSalaire_min(): ?int
    {
        return $this->salaire_min;
    }

    public function setSalaire_min(?int $salaire_min): self
    {
        $this->salaire_min = $salaire_min;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $salaire_max = null;

    public function getSalaire_max(): ?int
    {
        return $this->salaire_max;
    }

    public function setSalaire_max(?int $salaire_max): self
    {
        $this->salaire_max = $salaire_max;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $score_minimum = null;

    public function getScore_minimum(): ?int
    {
        return $this->score_minimum;
    }

    public function setScore_minimum(int $score_minimum): self
    {
        $this->score_minimum = $score_minimum;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $duree_test_minutes = null;

    public function getDuree_test_minutes(): ?int
    {
        return $this->duree_test_minutes;
    }

    public function setDuree_test_minutes(int $duree_test_minutes): self
    {
        $this->duree_test_minutes = $duree_test_minutes;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_publication = null;

    public function getDate_publication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDate_publication(\DateTimeInterface $date_publication): self
    {
        $this->date_publication = $date_publication;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'offre')]
    private Collection $candidatures;

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

    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'offre')]
    private Collection $questions;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->questions = new ArrayCollection();
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        if (!$this->questions instanceof Collection) {
            $this->questions = new ArrayCollection();
        }
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->getQuestions()->contains($question)) {
            $this->getQuestions()->add($question);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        $this->getQuestions()->removeElement($question);
        return $this;
    }

    public function getIdOffre(): ?int
    {
        return $this->id_offre;
    }

    public function getTypeContrat(): ?string
    {
        return $this->type_contrat;
    }

    public function setTypeContrat(?string $type_contrat): static
    {
        $this->type_contrat = $type_contrat;

        return $this;
    }

    public function getSalaireMin(): ?int
    {
        return $this->salaire_min;
    }

    public function setSalaireMin(?int $salaire_min): static
    {
        $this->salaire_min = $salaire_min;

        return $this;
    }

    public function getSalaireMax(): ?int
    {
        return $this->salaire_max;
    }

    public function setSalaireMax(?int $salaire_max): static
    {
        $this->salaire_max = $salaire_max;

        return $this;
    }

    public function getScoreMinimum(): ?int
    {
        return $this->score_minimum;
    }

    public function setScoreMinimum(int $score_minimum): static
    {
        $this->score_minimum = $score_minimum;

        return $this;
    }

    public function getDureeTestMinutes(): ?int
    {
        return $this->duree_test_minutes;
    }

    public function setDureeTestMinutes(int $duree_test_minutes): static
    {
        $this->duree_test_minutes = $duree_test_minutes;

        return $this;
    }

    public function getDatePublication(): ?\DateTime
    {
        return $this->date_publication;
    }

    public function setDatePublication(\DateTime $date_publication): static
    {
        $this->date_publication = $date_publication;

        return $this;
    }

}
