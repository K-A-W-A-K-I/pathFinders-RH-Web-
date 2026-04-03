<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\Table(name: 'offres')]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_offre')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le domaine est obligatoire.')]
    #[Assert\Choice(choices: ['IT', 'Finance', 'Marketing', 'RH', 'Vente', 'Production'], message: 'Domaine invalide.')]
    private ?string $domaine = null;

    #[ORM\Column(name: 'type_contrat', length: 50)]
    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire.')]
    #[Assert\Choice(choices: ['CDI', 'CDD', 'Stage', 'Freelance'], message: 'Type de contrat invalide.')]
    private ?string $typeContrat = null;

    #[ORM\Column(name: 'salaire_min')]
    #[Assert\NotNull(message: 'Le salaire minimum est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le salaire minimum doit être positif ou nul.')]
    private int $salaireMin = 0;

    #[ORM\Column(name: 'salaire_max')]
    #[Assert\NotNull(message: 'Le salaire maximum est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le salaire maximum doit être positif ou nul.')]
    private int $salaireMax = 0;

    #[ORM\Column(name: 'score_minimum')]
    #[Assert\NotNull(message: 'Le score minimum est obligatoire.')]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Le score minimum doit être entre {{ min }} et {{ max }}.')]
    private int $scoreMinimum = 50;

    #[ORM\Column(name: 'duree_test_minutes')]
    #[Assert\NotNull(message: 'La durée du test est obligatoire.')]
    #[Assert\Positive(message: 'La durée du test doit être supérieure à 0.')]
    #[Assert\Range(min: 5, max: 180, notInRangeMessage: 'La durée doit être entre {{ min }} et {{ max }} minutes.')]
    private int $dureeTestMinutes = 30;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['active', 'inactive', 'cloturee'], message: 'Statut invalide.')]
    private string $statut = 'active';

    #[ORM\Column(name: 'date_publication', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\OneToMany(mappedBy: 'offre', targetEntity: Question::class, cascade: ['remove'])]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'offre', targetEntity: Candidature::class, cascade: ['remove'])]
    private Collection $candidatures;

    public function __construct()
    {
        $this->questions    = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
        $this->datePublication = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getDomaine(): ?string { return $this->domaine; }
    public function setDomaine(string $domaine): static { $this->domaine = $domaine; return $this; }
    public function getTypeContrat(): ?string { return $this->typeContrat; }
    public function setTypeContrat(string $typeContrat): static { $this->typeContrat = $typeContrat; return $this; }
    public function getSalaireMin(): int { return $this->salaireMin; }
    public function setSalaireMin(int $salaireMin): static { $this->salaireMin = $salaireMin; return $this; }
    public function getSalaireMax(): int { return $this->salaireMax; }
    public function setSalaireMax(int $salaireMax): static { $this->salaireMax = $salaireMax; return $this; }
    public function getScoreMinimum(): int { return $this->scoreMinimum; }
    public function setScoreMinimum(int $scoreMinimum): static { $this->scoreMinimum = $scoreMinimum; return $this; }
    public function getDureeTestMinutes(): int { return $this->dureeTestMinutes; }
    public function setDureeTestMinutes(int $dureeTestMinutes): static { $this->dureeTestMinutes = $dureeTestMinutes; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getDatePublication(): ?\DateTimeInterface { return $this->datePublication; }
    public function setDatePublication(?\DateTimeInterface $datePublication): static { $this->datePublication = $datePublication; return $this; }
    public function getQuestions(): Collection { return $this->questions; }
    public function getCandidatures(): Collection { return $this->candidatures; }

    #[Assert\Callback]
    public function validateSalaires(ExecutionContextInterface $context): void
    {
        if ($this->salaireMax < $this->salaireMin) {
            $context->buildViolation('Le salaire maximum doit être supérieur ou égal au salaire minimum.')
                ->atPath('salaireMax')
                ->addViolation();
        }
    }
}
