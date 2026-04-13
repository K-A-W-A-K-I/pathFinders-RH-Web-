<?php

namespace App\Entity;

use App\Repository\ContenuModuleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContenuModuleRepository::class)]
#[ORM\Table(name: 'contenu_module')]
class ContenuModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_contenu')]
    private ?int $idContenu = null;

    #[ORM\ManyToOne(inversedBy: 'contenuModules')]
    #[ORM\JoinColumn(name: 'id_formation', referencedColumnName: 'id_formation', nullable: false)]
    #[Assert\NotNull(message: 'La formation est obligatoire.')]
    private ?Formation $formation = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le nom du module est obligatoire.')]
    #[Assert\Length(min: 2, max: 200, minMessage: 'Minimum 2 caractères.', maxMessage: 'Maximum 200 caractères.')]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: 'L\'ordre doit être un nombre positif ou zéro.')]
    private ?int $ordre = null;

    public function getIdContenu(): ?int { return $this->idContenu; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): static { $this->formation = $formation; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getOrdre(): ?int { return $this->ordre; }
    public function setOrdre(?int $ordre): static { $this->ordre = $ordre; return $this; }
}
