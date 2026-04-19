<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamation')]
#[ORM\HasLifecycleCallbacks]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reclamation')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_utilisateur')]
    private int $idUtilisateur;

    #[ORM\Column(length: 255)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'date_incident', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateIncident = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'moderation_decision', length: 20, nullable: true)]
    private ?string $moderationDecision = null;

    #[ORM\Column(name: 'moderation_reason', type: 'text', nullable: true)]
    private ?string $moderationReason = null;

    #[ORM\Column(name: 'moderation_score', type: 'float', nullable: true)]
    private ?float $moderationScore = null;

    #[ORM\Column(name: 'moderation_flags', type: 'json', nullable: true)]
    private ?array $moderationFlags = null;

    #[ORM\Column(name: 'moderation_summary', type: 'text', nullable: true)]
    private ?string $moderationSummary = null;

    #[ORM\Column(name: 'moderation_urgency', length: 20, nullable: true)]
    private ?string $moderationUrgency = null;

    #[ORM\Column(name: 'moderation_sentiment', length: 20, nullable: true)]
    private ?string $moderationSentiment = null;

    #[ORM\Column(name: 'manual_review_required', type: 'boolean', options: ['default' => false])]
    private bool $manualReviewRequired = false;

    #[ORM\Column(name: 'admin_reply', type: 'text', nullable: true)]
    private ?string $adminReply = null;

    #[ORM\Column(name: 'admin_reply_sent_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $adminReplySentAt = null;

    #[ORM\Column(name: 'admin_reply_email_status', length: 20, nullable: true)]
    private ?string $adminReplyEmailStatus = null;

    #[ORM\Column(name: 'admin_reply_email_error', type: 'text', nullable: true)]
    private ?string $adminReplyEmailError = null;

    #[ORM\Column(name: 'analytics_urgency_score', type: 'float', nullable: true)]
    private ?float $analyticsUrgencyScore = null;

    #[ORM\Column(name: 'analytics_priority_level', length: 20, nullable: true)]
    private ?string $analyticsPriorityLevel = null;

    #[ORM\Column(name: 'analytics_anomaly_score', type: 'float', nullable: true)]
    private ?float $analyticsAnomalyScore = null;

    #[ORM\Column(name: 'analytics_anomaly_flag', type: 'boolean', options: ['default' => false])]
    private bool $analyticsAnomalyFlag = false;

    #[ORM\Column(name: 'analytics_anomaly_reasons', type: 'json', nullable: true)]
    private ?array $analyticsAnomalyReasons = null;

    #[ORM\Column(name: 'analytics_last_calculated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $analyticsLastCalculatedAt = null;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'En attente'])]
    private ?string $statut = 'En attente';

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(name: 'date_modification', type: 'datetime')]
    private \DateTimeInterface $dateModification;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreation    = new \DateTime();
        $this->dateModification = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateModification = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $v): static { $this->idUtilisateur = $v; return $this; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $v): static { $this->titre = $v; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $v): static { $this->description = $v; return $this; }
    public function getDateIncident(): ?\DateTimeInterface { return $this->dateIncident; }
    public function setDateIncident(?\DateTimeInterface $v): static { $this->dateIncident = $v; return $this; }
    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $v): static { $this->lieu = $v; return $this; }
    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $v): static { $this->latitude = $v; return $this; }
    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $v): static { $this->longitude = $v; return $this; }
    public function getModerationDecision(): ?string { return $this->moderationDecision; }
    public function setModerationDecision(?string $v): static { $this->moderationDecision = $v; return $this; }
    public function getModerationReason(): ?string { return $this->moderationReason; }
    public function setModerationReason(?string $v): static { $this->moderationReason = $v; return $this; }
    public function getModerationScore(): ?float { return $this->moderationScore; }
    public function setModerationScore(?float $v): static { $this->moderationScore = $v; return $this; }
    public function getModerationFlags(): ?array { return $this->moderationFlags; }
    public function setModerationFlags(?array $v): static { $this->moderationFlags = $v; return $this; }
    public function getModerationSummary(): ?string { return $this->moderationSummary; }
    public function setModerationSummary(?string $v): static { $this->moderationSummary = $v; return $this; }
    public function getModerationUrgency(): ?string { return $this->moderationUrgency; }
    public function setModerationUrgency(?string $v): static { $this->moderationUrgency = $v; return $this; }
    public function getModerationSentiment(): ?string { return $this->moderationSentiment; }
    public function setModerationSentiment(?string $v): static { $this->moderationSentiment = $v; return $this; }
    public function isManualReviewRequired(): bool { return $this->manualReviewRequired; }
    public function setManualReviewRequired(bool $v): static { $this->manualReviewRequired = $v; return $this; }
    public function getAdminReply(): ?string { return $this->adminReply; }
    public function setAdminReply(?string $v): static { $this->adminReply = $v; return $this; }
    public function getAdminReplySentAt(): ?\DateTimeInterface { return $this->adminReplySentAt; }
    public function setAdminReplySentAt(?\DateTimeInterface $v): static { $this->adminReplySentAt = $v; return $this; }
    public function getAdminReplyEmailStatus(): ?string { return $this->adminReplyEmailStatus; }
    public function setAdminReplyEmailStatus(?string $v): static { $this->adminReplyEmailStatus = $v; return $this; }
    public function getAdminReplyEmailError(): ?string { return $this->adminReplyEmailError; }
    public function setAdminReplyEmailError(?string $v): static { $this->adminReplyEmailError = $v; return $this; }
    public function getAnalyticsUrgencyScore(): ?float { return $this->analyticsUrgencyScore; }
    public function setAnalyticsUrgencyScore(?float $v): static { $this->analyticsUrgencyScore = $v; return $this; }
    public function getAnalyticsPriorityLevel(): ?string { return $this->analyticsPriorityLevel; }
    public function setAnalyticsPriorityLevel(?string $v): static { $this->analyticsPriorityLevel = $v; return $this; }
    public function getAnalyticsAnomalyScore(): ?float { return $this->analyticsAnomalyScore; }
    public function setAnalyticsAnomalyScore(?float $v): static { $this->analyticsAnomalyScore = $v; return $this; }
    public function isAnalyticsAnomalyFlag(): bool { return $this->analyticsAnomalyFlag; }
    public function setAnalyticsAnomalyFlag(bool $v): static { $this->analyticsAnomalyFlag = $v; return $this; }
    public function getAnalyticsAnomalyReasons(): ?array { return $this->analyticsAnomalyReasons; }
    public function setAnalyticsAnomalyReasons(?array $v): static { $this->analyticsAnomalyReasons = $v; return $this; }
    public function getAnalyticsLastCalculatedAt(): ?\DateTimeInterface { return $this->analyticsLastCalculatedAt; }
    public function setAnalyticsLastCalculatedAt(?\DateTimeInterface $v): static { $this->analyticsLastCalculatedAt = $v; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $v): static { $this->statut = $v; return $this; }
    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function getDateModification(): \DateTimeInterface { return $this->dateModification; }
}
