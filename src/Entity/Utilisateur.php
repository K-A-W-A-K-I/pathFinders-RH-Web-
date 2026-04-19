<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateurs')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface, TotpTwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.')]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.')]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(name: 'mot_de_passe')]
    private string $password = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $role = 'ROLE_USER';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $statut = 'actif';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    // Colonnes Java existantes — mappées pour éviter les diffs de schéma
    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'imageUrl', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'totp_secret', length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(name: 'two_factor_enabled', nullable: true)]
    private ?bool $twoFactorEnabled = false;

    #[ORM\Column(name: 'face_data', type: 'text', nullable: true)]
    private ?string $faceData = null;

    #[ORM\Column(name: 'reset_token', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_expiry', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetExpiry = null;

    #[ORM\Column(name: 'analytics_trust_score', type: 'float', nullable: true)]
    private ?float $analyticsTrustScore = null;

    #[ORM\Column(name: 'analytics_trust_level', length: 20, nullable: true)]
    private ?string $analyticsTrustLevel = null;

    #[ORM\Column(name: 'analytics_trust_flags', type: 'json', nullable: true)]
    private ?array $analyticsTrustFlags = null;

    #[ORM\Column(name: 'analytics_last_calculated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $analyticsLastCalculatedAt = null;

    // Non mappé — utilisé uniquement pendant l'inscription
    private ?string $plainPassword = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function getPlainPassword(): ?string { return $this->plainPassword; }
    public function setPlainPassword(?string $p): static { $this->plainPassword = $p; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): static { $this->statut = $statut; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }
    public function getTotpSecret(): ?string { return $this->totpSecret; }
    public function setTotpSecret(?string $totpSecret): static { $this->totpSecret = $totpSecret; return $this; }
    public function isTwoFactorEnabled(): bool { return (bool) $this->twoFactorEnabled; }
    public function setTwoFactorEnabled(bool $twoFactorEnabled): static { $this->twoFactorEnabled = $twoFactorEnabled; return $this; }
    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }
    public function getResetExpiry(): ?\DateTimeInterface { return $this->resetExpiry; }
    public function setResetExpiry(?\DateTimeInterface $resetExpiry): static { $this->resetExpiry = $resetExpiry; return $this; }
    public function getAnalyticsTrustScore(): ?float { return $this->analyticsTrustScore; }
    public function setAnalyticsTrustScore(?float $analyticsTrustScore): static { $this->analyticsTrustScore = $analyticsTrustScore; return $this; }
    public function getAnalyticsTrustLevel(): ?string { return $this->analyticsTrustLevel; }
    public function setAnalyticsTrustLevel(?string $analyticsTrustLevel): static { $this->analyticsTrustLevel = $analyticsTrustLevel; return $this; }
    public function getAnalyticsTrustFlags(): ?array { return $this->analyticsTrustFlags; }
    public function setAnalyticsTrustFlags(?array $analyticsTrustFlags): static { $this->analyticsTrustFlags = $analyticsTrustFlags; return $this; }
    public function getAnalyticsLastCalculatedAt(): ?\DateTimeInterface { return $this->analyticsLastCalculatedAt; }
    public function setAnalyticsLastCalculatedAt(?\DateTimeInterface $analyticsLastCalculatedAt): static { $this->analyticsLastCalculatedAt = $analyticsLastCalculatedAt; return $this; }
    public function getFullName(): string { return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')); }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->isTwoFactorEnabled() && !empty($this->totpSecret);
    }

    public function getTotpAuthenticationUsername(): string
    {
        return (string) $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if (empty($this->totpSecret)) {
            return null;
        }

        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    // UserInterface
    public function getRoles(): array
    {
        $role = $this->role ?? 'ROLE_USER';
        // Normalize legacy roles without the ROLE_ prefix
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . strtoupper($role);
        }
        return array_unique([$role, 'ROLE_USER']);
    }
    public function getUserIdentifier(): string { return (string) $this->email; }
    public function eraseCredentials(): void { $this->plainPassword = null; }
}
