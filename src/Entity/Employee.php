<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EmployeeRepository;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employee')]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_employee = null;

    public function getId_employee(): ?int
    {
        return $this->id_employee;
    }

    public function setId_employee(int $id_employee): self
    {
        $this->id_employee = $id_employee;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'employees')]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $score = null;

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $salaire = null;

    public function getSalaire(): ?float
    {
        return $this->salaire;
    }

    public function setSalaire(float $salaire): self
    {
        $this->salaire = $salaire;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Absence::class, mappedBy: 'employee')]
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

    #[ORM\OneToMany(targetEntity: Conge::class, mappedBy: 'employee')]
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

    #[ORM\OneToMany(targetEntity: FichesPaiement::class, mappedBy: 'employee')]
    private Collection $fichesPaiements;

    public function __construct()
    {
        $this->absences = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->fichesPaiements = new ArrayCollection();
    }

    /**
     * @return Collection<int, FichesPaiement>
     */
    public function getFichesPaiements(): Collection
    {
        if (!$this->fichesPaiements instanceof Collection) {
            $this->fichesPaiements = new ArrayCollection();
        }
        return $this->fichesPaiements;
    }

    public function addFichesPaiement(FichesPaiement $fichesPaiement): self
    {
        if (!$this->getFichesPaiements()->contains($fichesPaiement)) {
            $this->getFichesPaiements()->add($fichesPaiement);
        }
        return $this;
    }

    public function removeFichesPaiement(FichesPaiement $fichesPaiement): self
    {
        $this->getFichesPaiements()->removeElement($fichesPaiement);
        return $this;
    }

    public function getIdEmployee(): ?int
    {
        return $this->id_employee;
    }

}
