<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'questions')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_question')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id_offre', nullable: false)]
    private ?Offre $offre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La question est obligatoire.')]
    #[Assert\Length(min: 5, minMessage: 'La question doit contenir au moins {{ limit }} caractères.')]
    private ?string $question = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le choix 1 est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le choix 1 ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $choix1 = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le choix 2 est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le choix 2 ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $choix2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le choix 3 ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $choix3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le choix 4 ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $choix4 = null;

    #[ORM\Column(name: 'bonne_reponse')]
    #[Assert\NotNull(message: 'La bonne réponse est obligatoire.')]
    #[Assert\Range(min: 1, max: 4, notInRangeMessage: 'La bonne réponse doit être entre {{ min }} et {{ max }}.')]
    private int $bonneReponse = 1;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Les points sont obligatoires.')]
    #[Assert\Positive(message: 'Les points doivent être supérieurs à 0.')]
    #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'Les points doivent être entre {{ min }} et {{ max }}.')]
    private int $points = 1;

    public function getId(): ?int { return $this->id; }
    public function getOffre(): ?Offre { return $this->offre; }
    public function setOffre(?Offre $offre): static { $this->offre = $offre; return $this; }
    public function getQuestion(): ?string { return $this->question; }
    public function setQuestion(string $question): static { $this->question = $question; return $this; }
    public function getChoix1(): ?string { return $this->choix1; }
    public function setChoix1(string $choix1): static { $this->choix1 = $choix1; return $this; }
    public function getChoix2(): ?string { return $this->choix2; }
    public function setChoix2(string $choix2): static { $this->choix2 = $choix2; return $this; }
    public function getChoix3(): ?string { return $this->choix3; }
    public function setChoix3(?string $choix3): static { $this->choix3 = $choix3; return $this; }
    public function getChoix4(): ?string { return $this->choix4; }
    public function setChoix4(?string $choix4): static { $this->choix4 = $choix4; return $this; }
    public function getBonneReponse(): int { return $this->bonneReponse; }
    public function setBonneReponse(int $bonneReponse): static { $this->bonneReponse = $bonneReponse; return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = $points; return $this; }

    public function getChoices(): array
    {
        return array_filter([
            1 => $this->choix1,
            2 => $this->choix2,
            3 => $this->choix3,
            4 => $this->choix4,
        ]);
    }
}
