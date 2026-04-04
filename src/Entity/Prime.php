<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PrimeRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrimeRepository::class)]
#[ORM\Table(name: 'prime')]
class Prime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idPrime', type: 'integer')]
    private ?int $idPrime = null;

    #[ORM\Column(name: 'libelle', type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Minimum 2 caractères', maxMessage: 'Maximum 100 caractères')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le libellé ne doit contenir que des lettres')]
    private ?string $libelle = null;

    #[ORM\Column(name: 'montant', type: 'decimal', nullable: true)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\Positive(message: 'Le montant doit être un nombre positif')]
    #[Assert\LessThan(value: 100000, message: 'Le montant ne peut pas dépasser 100 000')]
    private ?float $montant = null;

    #[ORM\Column(name: 'dateAttribution', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\LessThanOrEqual(value: 'today', message: 'La date ne peut pas être dans le futur')]
    private ?\DateTimeInterface $dateAttribution = null;

    #[ORM\Column(name: 'typePrime', type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le type de prime est obligatoire')]
    #[Assert\Choice(
        choices: ['performance', 'anciennete', 'exceptionnelle'],
        message: 'Type invalide'
    )]
    private ?string $typePrime = null;

    #[ORM\ManyToOne(targetEntity: FichesPaiement::class, inversedBy: 'primes')]
    #[ORM\JoinColumn(name: 'id_fiche_paiement', referencedColumnName: 'id_fiche_paiement')]
    #[Assert\NotNull(message: 'La fiche de paiement est obligatoire')]
    private ?FichesPaiement $fichesPaiement = null;

    // all your getters/setters stay exactly the same
    public function getId(): ?int { return $this->idPrime; }
    public function getIdPrime(): ?int { return $this->idPrime; }
    public function setIdPrime(int $idPrime): self { $this->idPrime = $idPrime; return $this; }
    public function getLibelle(): ?string { return $this->libelle; }
    public function setLibelle(?string $libelle): self { $this->libelle = $libelle; return $this; }
    public function getMontant(): ?float { return $this->montant; }
    public function setMontant(?float $montant): self { $this->montant = $montant; return $this; }
    public function getDateAttribution(): ?\DateTimeInterface { return $this->dateAttribution; }
    public function setDateAttribution(?\DateTimeInterface $dateAttribution): self { $this->dateAttribution = $dateAttribution; return $this; }
    public function getTypePrime(): ?string { return $this->typePrime; }
    public function setTypePrime(?string $typePrime): self { $this->typePrime = $typePrime; return $this; }
    public function getFichesPaiement(): ?FichesPaiement { return $this->fichesPaiement; }
    public function setFichesPaiement(?FichesPaiement $fichesPaiement): self { $this->fichesPaiement = $fichesPaiement; return $this; }
}