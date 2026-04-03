<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PrimeRepository;

#[ORM\Entity(repositoryClass: PrimeRepository::class)]
#[ORM\Table(name: 'prime')]
class Prime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idPrime = null;

    public function getIdPrime(): ?int
    {
        return $this->idPrime;
    }

    public function setIdPrime(int $idPrime): self
    {
        $this->idPrime = $idPrime;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $libelle = null;

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $montant = null;

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateAttribution = null;

    public function getDateAttribution(): ?\DateTimeInterface
    {
        return $this->dateAttribution;
    }

    public function setDateAttribution(?\DateTimeInterface $dateAttribution): self
    {
        $this->dateAttribution = $dateAttribution;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $typePrime = null;

    public function getTypePrime(): ?string
    {
        return $this->typePrime;
    }

    public function setTypePrime(?string $typePrime): self
    {
        $this->typePrime = $typePrime;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: FichesPaiement::class, inversedBy: 'primes')]
    #[ORM\JoinColumn(name: 'id_fiche_paiement', referencedColumnName: 'id_fiche_paiement')]
    private ?FichesPaiement $fichesPaiement = null;

    public function getFichesPaiement(): ?FichesPaiement
    {
        return $this->fichesPaiement;
    }

    public function setFichesPaiement(?FichesPaiement $fichesPaiement): self
    {
        $this->fichesPaiement = $fichesPaiement;
        return $this;
    }

}
