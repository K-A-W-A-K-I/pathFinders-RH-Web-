<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\QuestionRepository;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'questions')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_question = null;

    public function getId_question(): ?int
    {
        return $this->id_question;
    }

    public function setId_question(int $id_question): self
    {
        $this->id_question = $id_question;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id_offre')]
    private ?Offre $offre = null;

    public function getOffre(): ?Offre
    {
        return $this->offre;
    }

    public function setOffre(?Offre $offre): self
    {
        $this->offre = $offre;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $question = null;

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $choix1 = null;

    public function getChoix1(): ?string
    {
        return $this->choix1;
    }

    public function setChoix1(string $choix1): self
    {
        $this->choix1 = $choix1;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $choix2 = null;

    public function getChoix2(): ?string
    {
        return $this->choix2;
    }

    public function setChoix2(string $choix2): self
    {
        $this->choix2 = $choix2;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $choix3 = null;

    public function getChoix3(): ?string
    {
        return $this->choix3;
    }

    public function setChoix3(string $choix3): self
    {
        $this->choix3 = $choix3;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $choix4 = null;

    public function getChoix4(): ?string
    {
        return $this->choix4;
    }

    public function setChoix4(string $choix4): self
    {
        $this->choix4 = $choix4;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bonne_reponse = null;

    public function getBonne_reponse(): ?int
    {
        return $this->bonne_reponse;
    }

    public function setBonne_reponse(?int $bonne_reponse): self
    {
        $this->bonne_reponse = $bonne_reponse;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $points = null;

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getIdQuestion(): ?int
    {
        return $this->id_question;
    }

    public function getBonneReponse(): ?int
    {
        return $this->bonne_reponse;
    }

    public function setBonneReponse(?int $bonne_reponse): static
    {
        $this->bonne_reponse = $bonne_reponse;

        return $this;
    }

}
