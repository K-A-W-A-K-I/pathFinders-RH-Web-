<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\UtilisateurRepository;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateurs')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_utilisateur = null;

    public function getId_utilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur(int $id_utilisateur): self
    {
        $this->id_utilisateur = $id_utilisateur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $mot_de_passe = null;

    public function getMot_de_passe(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMot_de_passe(string $mot_de_passe): self
    {
        $this->mot_de_passe = $mot_de_passe;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephone = null;

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_creation = null;

    public function getDate_creation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDate_creation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

 

    

    #[ORM\OneToMany(targetEntity: Absence::class, mappedBy: 'utilisateur')]
    private Collection $absences;

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        if (!$this->absences instanceof Collection) {
            $this->absences = new ArrayCollection();
        }
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->getAbsences()->contains($absence)) {
            $this->getAbsences()->add($absence);
        }
        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        $this->getAbsences()->removeElement($absence);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Candidat::class, mappedBy: 'utilisateur')]
    private Collection $candidats;

    /**
     * @return Collection<int, Candidat>
     */
    public function getCandidats(): Collection
    {
        if (!$this->candidats instanceof Collection) {
            $this->candidats = new ArrayCollection();
        }
        return $this->candidats;
    }

    public function addCandidat(Candidat $candidat): self
    {
        if (!$this->getCandidats()->contains($candidat)) {
            $this->getCandidats()->add($candidat);
        }
        return $this;
    }

    public function removeCandidat(Candidat $candidat): self
    {
        $this->getCandidats()->removeElement($candidat);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Conge::class, mappedBy: 'utilisateur')]
    private Collection $conges;

    /**
     * @return Collection<int, Conge>
     */
    public function getConges(): Collection
    {
        if (!$this->conges instanceof Collection) {
            $this->conges = new ArrayCollection();
        }
        return $this->conges;
    }

    public function addConge(Conge $conge): self
    {
        if (!$this->getConges()->contains($conge)) {
            $this->getConges()->add($conge);
        }
        return $this;
    }

    public function removeConge(Conge $conge): self
    {
        $this->getConges()->removeElement($conge);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'utilisateur')]
    private Collection $employees;

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        if (!$this->employees instanceof Collection) {
            $this->employees = new ArrayCollection();
        }
        return $this->employees;
    }

    public function addEmployee(Employee $employee): self
    {
        if (!$this->getEmployees()->contains($employee)) {
            $this->getEmployees()->add($employee);
        }
        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        $this->getEmployees()->removeElement($employee);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'utilisateur')]
    private Collection $evenements;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        if (!$this->evenements instanceof Collection) {
            $this->evenements = new ArrayCollection();
        }
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        if (!$this->getEvenements()->contains($evenement)) {
            $this->getEvenements()->add($evenement);
        }
        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        $this->getEvenements()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: InscriptionEvenement::class, mappedBy: 'utilisateur')]
    private ?InscriptionEvenement $inscriptionEvenement = null;

    public function getInscriptionEvenement(): ?InscriptionEvenement
    {
        return $this->inscriptionEvenement;
    }

    public function setInscriptionEvenement(?InscriptionEvenement $inscriptionEvenement): self
    {
        $this->inscriptionEvenement = $inscriptionEvenement;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: InscriptionsFormation::class, mappedBy: 'utilisateur')]
    private ?InscriptionsFormation $inscriptionsFormation = null;

    public function getInscriptionsFormation(): ?InscriptionsFormation
    {
        return $this->inscriptionsFormation;
    }

    public function setInscriptionsFormation(?InscriptionsFormation $inscriptionsFormation): self
    {
        $this->inscriptionsFormation = $inscriptionsFormation;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Planning::class, mappedBy: 'utilisateur')]
    private Collection $plannings;

    public function __construct()
    {
        $this->absences = new ArrayCollection();
        $this->candidats = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->employees = new ArrayCollection();
        $this->evenements = new ArrayCollection();
        $this->plannings = new ArrayCollection();
    }

    /**
     * @return Collection<int, Planning>
     */
    public function getPlannings(): Collection
    {
        if (!$this->plannings instanceof Collection) {
            $this->plannings = new ArrayCollection();
        }
        return $this->plannings;
    }

    public function addPlanning(Planning $planning): self
    {
        if (!$this->getPlannings()->contains($planning)) {
            $this->getPlannings()->add($planning);
        }
        return $this;
    }

    public function removePlanning(Planning $planning): self
    {
        $this->getPlannings()->removeElement($planning);
        return $this;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function getMotDePasse(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): static
    {
        $this->mot_de_passe = $mot_de_passe;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

}
