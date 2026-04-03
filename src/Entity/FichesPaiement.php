<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\FichesPaiementRepository;

#[ORM\Entity(repositoryClass: FichesPaiementRepository::class)]
#[ORM\Table(name: 'fiches_paiement')]
class FichesPaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_fiche_paiement = null;

    public function getId_fiche_paiement(): ?int
    {
        return $this->id_fiche_paiement;
    }

    public function setId_fiche_paiement(int $id_fiche_paiement): self
    {
        $this->id_fiche_paiement = $id_fiche_paiement;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'fichesPaiements')]
    #[ORM\JoinColumn(name: 'id_employe', referencedColumnName: 'id_employee')]
    private ?Employee $employee = null;

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $montant_deduction = null;

    public function getMontant_deduction(): ?float
    {
        return $this->montant_deduction;
    }

    public function setMontant_deduction(?float $montant_deduction): self
    {
        $this->montant_deduction = $montant_deduction;
        return $this;
    }

    // ← $primes float column REMOVED (name conflict with the relation below)

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $montant_taxe = null;

    public function getMontant_taxe(): ?float
    {
        return $this->montant_taxe;
    }

    public function setMontant_taxe(?float $montant_taxe): self
    {
        $this->montant_taxe = $montant_taxe;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_paiement = null;

    public function getType_paiement(): ?string
    {
        return $this->type_paiement;
    }

    public function setType_paiement(?string $type_paiement): self
    {
        $this->type_paiement = $type_paiement;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_paiement = null;

    public function getDate_paiement(): ?\DateTimeInterface
    {
        return $this->date_paiement;
    }

    public function setDate_paiement(?\DateTimeInterface $date_paiement): self
    {
        $this->date_paiement = $date_paiement;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Prime::class, mappedBy: 'fichesPaiement')]
    private Collection $primes;

    public function __construct()
    {
        $this->primes = new ArrayCollection();
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

    public function getIdFichePaiement(): ?int
    {
        return $this->id_fiche_paiement;
    }

    public function getMontantDeduction(): ?string
    {
        return $this->montant_deduction;
    }

    public function setMontantDeduction(?string $montant_deduction): static
    {
        $this->montant_deduction = $montant_deduction;

        return $this;
    }

    public function getMontantTaxe(): ?string
    {
        return $this->montant_taxe;
    }

    public function setMontantTaxe(?string $montant_taxe): static
    {
        $this->montant_taxe = $montant_taxe;

        return $this;
    }

    public function getTypePaiement(): ?string
    {
        return $this->type_paiement;
    }

    public function setTypePaiement(?string $type_paiement): static
    {
        $this->type_paiement = $type_paiement;

        return $this;
    }

    public function getDatePaiement(): ?\DateTime
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(?\DateTime $date_paiement): static
    {
        $this->date_paiement = $date_paiement;

        return $this;
    }
}