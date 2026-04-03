<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ContenuModuleRepository;

#[ORM\Entity(repositoryClass: ContenuModuleRepository::class)]
#[ORM\Table(name: 'contenu_module')]
class ContenuModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_contenu = null;

    public function getId_contenu(): ?int
    {
        return $this->id_contenu;
    }

    public function setId_contenu(int $id_contenu): self
    {
        $this->id_contenu = $id_contenu;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'contenuModules')]
    #[ORM\JoinColumn(name: 'id_formation', referencedColumnName: 'id_formation')]
    private ?Formation $formation = null;

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $contenu = null;

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $ordre = null;

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getIdContenu(): ?int
    {
        return $this->id_contenu;
    }

}
