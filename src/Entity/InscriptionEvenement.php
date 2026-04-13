<?php
 
namespace App\Entity;
 
use App\Repository\InscriptionEvenementRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
 
#[ORM\Entity(repositoryClass: InscriptionEvenementRepository::class)]
#[ORM\Table(name: 'inscription_evenement')]
#[ORM\UniqueConstraint(name: 'uq_user_event', columns: ['id_evenement', 'id_utilisateur'])]
class InscriptionEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_inscription', type: Types::INTEGER)]
    private ?int $id = null;
 
    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement', nullable: false, onDelete: 'CASCADE')]
    private ?Evenement $evenement = null;
 
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;
 
    #[ORM\Column(name: 'statut_inscription', type: Types::STRING, length: 20)]
    private string $statutInscription = 'CONFIRME';
 
    #[ORM\Column(name: 'date_inscription', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateInscription = null;
 
    #[ORM\Column(name: 'statut_paiement', type: Types::STRING, length: 20, nullable: true)]
    private ?string $statutPaiement = null;
 
    #[ORM\Column(name: 'date_paiement', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePaiement = null;
 
    #[ORM\Column(name: 'date_remboursement', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRemboursement = null;
 
    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }
 
    public function getId(): ?int { return $this->id; }
 
    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(?Evenement $evenement): static { $this->evenement = $evenement; return $this; }
 
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
 
    public function getStatutInscription(): string { return $this->statutInscription; }
    public function setStatutInscription(string $statutInscription): static { $this->statutInscription = $statutInscription; return $this; }
 
    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }
 
    public function getStatutPaiement(): ?string { return $this->statutPaiement; }
    public function setStatutPaiement(?string $statutPaiement): static { $this->statutPaiement = $statutPaiement; return $this; }
 
    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(?\DateTimeInterface $datePaiement): static { $this->datePaiement = $datePaiement; return $this; }
 
    public function getDateRemboursement(): ?\DateTimeInterface { return $this->dateRemboursement; }
    public function setDateRemboursement(?\DateTimeInterface $dateRemboursement): static { $this->dateRemboursement = $dateRemboursement; return $this; }
}