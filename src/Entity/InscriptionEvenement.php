<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\InscriptionEvenementRepository;

#[ORM\Entity(repositoryClass: InscriptionEvenementRepository::class)]
#[ORM\Table(name: 'inscription_evenement')]
class InscriptionEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_inscription = null;

    public function getId_inscription(): ?int
    {
        return $this->id_inscription;
    }

    public function setId_inscription(int $id_inscription): self
    {
        $this->id_inscription = $id_inscription;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Evenement::class, inversedBy: 'inscriptionEvenement')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement', unique: true)]
    private ?Evenement $evenement = null;

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Utilisateur::class, inversedBy: 'inscriptionEvenement')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', unique: true)]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut_inscription = null;

    public function getStatut_inscription(): ?string
    {
        return $this->statut_inscription;
    }

    public function setStatut_inscription(string $statut_inscription): self
    {
        $this->statut_inscription = $statut_inscription;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_inscription = null;

    public function getDate_inscription(): ?\DateTimeInterface
    {
        return $this->date_inscription;
    }

    public function setDate_inscription(\DateTimeInterface $date_inscription): self
    {
        $this->date_inscription = $date_inscription;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut_paiement = null;

    public function getStatut_paiement(): ?string
    {
        return $this->statut_paiement;
    }

    public function setStatut_paiement(?string $statut_paiement): self
    {
        $this->statut_paiement = $statut_paiement;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_remboursement = null;

    public function getDate_remboursement(): ?\DateTimeInterface
    {
        return $this->date_remboursement;
    }

    public function setDate_remboursement(?\DateTimeInterface $date_remboursement): self
    {
        $this->date_remboursement = $date_remboursement;
        return $this;
    }

    public function getIdInscription(): ?int
    {
        return $this->id_inscription;
    }

    public function getStatutInscription(): ?string
    {
        return $this->statut_inscription;
    }

    public function setStatutInscription(string $statut_inscription): static
    {
        $this->statut_inscription = $statut_inscription;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->date_inscription;
    }

    public function setDateInscription(\DateTime $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    public function getStatutPaiement(): ?string
    {
        return $this->statut_paiement;
    }

    public function setStatutPaiement(?string $statut_paiement): static
    {
        $this->statut_paiement = $statut_paiement;

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

    public function getDateRemboursement(): ?\DateTime
    {
        return $this->date_remboursement;
    }

    public function setDateRemboursement(?\DateTime $date_remboursement): static
    {
        $this->date_remboursement = $date_remboursement;

        return $this;
    }

}
