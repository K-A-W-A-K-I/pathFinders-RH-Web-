<?php

namespace App\Entity;

use App\Repository\FichesPaiementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: FichesPaiementRepository::class)]
#[ORM\Table(name: 'fiches_paiement')]
#[Assert\Callback('validateDateCurrentMonth')]
class FichesPaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_fiche_paiement = null;

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'fichesPaiements')]
    #[ORM\JoinColumn(name: 'id_employe', referencedColumnName: 'id_employee')]
    #[Assert\NotNull(message: "L'employé est obligatoire")]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'decimal', nullable: true)]
    #[Assert\PositiveOrZero(message: 'La déduction ne peut pas être négative')]
    private ?float $montant_deduction = null;

    #[ORM\Column(type: 'decimal', nullable: true)]
    #[Assert\NotBlank(message: 'Le montant de taxe est obligatoire')]
    #[Assert\PositiveOrZero(message: 'La taxe ne peut pas être négative')]
    #[Assert\LessThan(value: 100000, message: 'Le montant ne peut pas dépasser 100 000')]
    private ?float $montant_taxe = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le type de paiement est obligatoire')]
    #[Assert\Choice(
        choices: ['virement', 'cheque', 'especes'],
        message: 'Type de paiement invalide'
    )]
    private ?string $type_paiement = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotBlank(message: 'La date de paiement est obligatoire')]
    
    // AFTER
    //////////////////
    private ?\DateTimeInterface $date_paiement = null;

    #[ORM\OneToMany(targetEntity: Prime::class, mappedBy: 'fichesPaiement')]
    private Collection $primes;

    public function __construct()
    {
        $this->primes = new ArrayCollection();
    }

    // ── getters / setters ──────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id_fiche_paiement;
    }

    public function getIdFichePaiement(): ?int
    {
        return $this->id_fiche_paiement;
    }

    public function getId_fiche_paiement(): ?int
    {
        return $this->id_fiche_paiement;
    }

    public function setId_fiche_paiement(int $id_fiche_paiement): self
    {
        $this->id_fiche_paiement = $id_fiche_paiement;
        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    public function getMontantDeduction(): ?float
    {
        return $this->montant_deduction;
    }

    public function setMontantDeduction(?float $montant_deduction): self
    {
        $this->montant_deduction = $montant_deduction;
        return $this;
    }

    public function getMontant_deduction(): ?float
    {
        return $this->montant_deduction;
    }

    public function setMontant_deduction(?float $montant_deduction): self
    {
        $this->montant_deduction = $montant_deduction;
        return $this;
    }

    public function getMontantTaxe(): ?float
    {
        return $this->montant_taxe;
    }

    public function setMontantTaxe(?float $montant_taxe): self
    {
        $this->montant_taxe = $montant_taxe;
        return $this;
    }

    public function getMontant_taxe(): ?float
    {
        return $this->montant_taxe;
    }

    public function setMontant_taxe(?float $montant_taxe): self
    {
        $this->montant_taxe = $montant_taxe;
        return $this;
    }

    public function getTypePaiement(): ?string
    {
        return $this->type_paiement;
    }

    public function setTypePaiement(?string $type_paiement): self
    {
        $this->type_paiement = $type_paiement;
        return $this;
    }

    public function getType_paiement(): ?string
    {
        return $this->type_paiement;
    }

    public function setType_paiement(?string $type_paiement): self
    {
        $this->type_paiement = $type_paiement;
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(?\DateTimeInterface $date_paiement): self
    {
        $this->date_paiement = $date_paiement;
        return $this;
    }

    public function getDate_paiement(): ?\DateTimeInterface
    {
        return $this->date_paiement;
    }

    public function setDate_paiement(?\DateTimeInterface $date_paiement): self
    {
        $this->date_paiement = $date_paiement;
        return $this;
    }

    public function getPrimes(): Collection
    {
        return $this->primes;
    }

    public function addPrime(Prime $prime): self
    {
        if (!$this->primes->contains($prime)) {
            $this->primes->add($prime);
        }
        return $this;
    }

    public function removePrime(Prime $prime): self
    {
        $this->primes->removeElement($prime);
        return $this;
    }
    public function validateDateCurrentMonth(ExecutionContextInterface $context): void
{
    $date = $this->getDatePaiement();
    if (!$date) return;

    $now = new \DateTime();
    $currentMonth = (int) $now->format('m');
    $currentYear  = (int) $now->format('Y');
    $dateMonth    = (int) $date->format('m');
    $dateYear     = (int) $date->format('Y');

    if ($dateMonth !== $currentMonth || $dateYear !== $currentYear) {
        $context->buildViolation('La date doit être dans le mois en cours')
            ->atPath('date_paiement')
            ->addViolation();
    }
}
}