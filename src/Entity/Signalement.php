<?php
// src/Entity/Signalement.php

namespace App\Entity;

use App\Repository\SignalementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
#[ORM\Table(name: 'signalement')]
class Signalement
{
    // Constantes pour les motifs
    const MOTIF_SPAM = 'spam';
    const MOTIF_INAPPROPRIE = 'inapproprie';
    const MOTIF_FAUX = 'faux';
    const MOTIF_INJURIEUX = 'injureux';
    const MOTIF_HARCELEMENT = 'harcelement';
    const MOTIF_VIOLATION = 'violation';
    const MOTIF_AUTRE = 'autre';
    
    // Constantes pour les statuts
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_TRAITE = 'traite';
    const STATUT_REJETE = 'rejete';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'signalements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Avis $avis = null;

    #[ORM\Column(length: 255)]
    private ?string $motif = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, options: ['default' => 'en_attente'])]
    private ?string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Champs optionnels pour traçabilité
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailSignaleur = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = self::STATUT_EN_ATTENTE;
    }

    // ========== GETTERS ET SETTERS ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    public function setAvis(?Avis $avis): static
    {
        $this->avis = $avis;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getEmailSignaleur(): ?string
    {
        return $this->emailSignaleur;
    }

    public function setEmailSignaleur(?string $emailSignaleur): static
    {
        $this->emailSignaleur = $emailSignaleur;
        return $this;
    }

    // ========== MÉTHODES UTILITAIRES ==========

    public function getMotifTexte(): string
    {
        return match($this->motif) {
            self::MOTIF_SPAM => 'Spam ou publicité',
            self::MOTIF_INAPPROPRIE => 'Contenu inapproprié',
            self::MOTIF_FAUX => 'Information fausse ou trompeuse',
            self::MOTIF_INJURIEUX => 'Propos injurieux ou discriminatoires',
            self::MOTIF_HARCELEMENT => 'Harcèlement',
            self::MOTIF_VIOLATION => 'Violation des droits d\'auteur',
            self::MOTIF_AUTRE => 'Autre motif',
            default => 'Motif inconnu'
        };
    }

    public function getStatutTexte(): string
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_TRAITE => 'Traité',
            self::STATUT_REJETE => 'Rejeté',
            default => 'Inconnu'
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning',
            self::STATUT_TRAITE => 'success',
            self::STATUT_REJETE => 'danger',
            default => 'secondary'
        };
    }

    public function isTraite(): bool
    {
        return $this->statut === self::STATUT_TRAITE;
    }

    public function isRejete(): bool
    {
        return $this->statut === self::STATUT_REJETE;
    }

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }
}