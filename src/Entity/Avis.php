<?php
// src/Entity/Avis.php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    // ========== CONSTANTES DE STATUT ==========
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomClient = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?int $note = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateAvis = null;

    #[ORM\Column]
    private ?int $voyageId = null;

    // ========== STATUT POUR ADMIN ==========
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'pending'])]
    private ?string $status = self::STATUS_PENDING;

    // ========== GÉOLOCALISATION ET MÉTÉO ==========
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $weatherData = null;

    // ========== ANALYSE ==========
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $sentimentAnalysis = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sentimentScore = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $predictiveAnalysis = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $satisfactionScore = null;

    // ========== COMMENTAIRES ==========
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $commentaires = [];

    // ========== PHOTOS ==========
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $photos = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainPhoto = null;

    // ========== RELATIONS ==========
    #[ORM\OneToMany(targetEntity: Vote::class, mappedBy: 'avis', orphanRemoval: true)]
    private Collection $votes;

    // ========== RELATION SIGNALEMENT (NOUVEAU) ==========
    #[ORM\OneToMany(targetEntity: Signalement::class, mappedBy: 'avis', orphanRemoval: true)]
    private Collection $signalements;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->signalements = new ArrayCollection(); // AJOUTÉ
        $this->dateAvis = new \DateTime();
        $this->commentaires = [];
        $this->status = self::STATUS_PENDING;
    }

    // ========== GETTERS ET SETTERS ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomClient(): ?string
    {
        return $this->nomClient;
    }

    public function setNomClient(string $nomClient): static
    {
        $this->nomClient = $nomClient;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateAvis(): ?\DateTime
    {
        return $this->dateAvis;
    }

    public function setDateAvis(\DateTime $dateAvis): static
    {
        $this->dateAvis = $dateAvis;
        return $this;
    }

    public function getVoyageId(): ?int
    {
        return $this->voyageId;
    }

    public function setVoyageId(int $voyageId): static
    {
        $this->voyageId = $voyageId;
        return $this;
    }

    // ========== STATUT GETTERS ET SETTERS ==========

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    // ========== MÉTHODES UTILITAIRES POUR LE STATUT ==========

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(): static
    {
        $this->status = self::STATUS_APPROVED;
        return $this;
    }

    public function reject(): static
    {
        $this->status = self::STATUS_REJECTED;
        return $this;
    }

    public function setPending(): static
    {
        $this->status = self::STATUS_PENDING;
        return $this;
    }

    // ========== GÉOLOCALISATION ET MÉTÉO ==========

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): static
    {
        $this->destination = $destination;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getWeatherData(): ?array
    {
        return $this->weatherData;
    }

    public function setWeatherData(?array $weatherData): static
    {
        $this->weatherData = $weatherData;
        return $this;
    }

    // ========== ANALYSE ==========

    public function getSentimentAnalysis(): ?array
    {
        return $this->sentimentAnalysis;
    }

    public function setSentimentAnalysis(?array $sentimentAnalysis): static
    {
        $this->sentimentAnalysis = $sentimentAnalysis;
        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getSentimentScore(): ?float
    {
        return $this->sentimentScore;
    }

    public function setSentimentScore(?float $sentimentScore): static
    {
        $this->sentimentScore = $sentimentScore;
        return $this;
    }

    public function getPredictiveAnalysis(): ?array
    {
        return $this->predictiveAnalysis;
    }

    public function setPredictiveAnalysis(?array $predictiveAnalysis): static
    {
        $this->predictiveAnalysis = $predictiveAnalysis;
        return $this;
    }

    public function getSatisfactionScore(): ?float
    {
        return $this->satisfactionScore;
    }

    public function setSatisfactionScore(?float $satisfactionScore): static
    {
        $this->satisfactionScore = $satisfactionScore;
        return $this;
    }

    // ========== COMMENTAIRES ==========

    public function getCommentaires(): ?array
    {
        return $this->commentaires;
    }

    public function setCommentaires(?array $commentaires): static
    {
        $this->commentaires = $commentaires;
        return $this;
    }

    public function addCommentaire(string $auteur, string $texte): static
    {
        $commentaire = [
            'id' => uniqid(),
            'auteur' => $auteur,
            'texte' => $texte,
            'date' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
        
        if ($this->commentaires === null) {
            $this->commentaires = [];
        }
        
        $this->commentaires[] = $commentaire;
        return $this;
    }

    public function removeCommentaire(string $id): static
    {
        if ($this->commentaires) {
            foreach ($this->commentaires as $key => $commentaire) {
                if ($commentaire['id'] === $id) {
                    unset($this->commentaires[$key]);
                    $this->commentaires = array_values($this->commentaires);
                    break;
                }
            }
        }
        return $this;
    }

    public function getNombreCommentaires(): int
    {
        return $this->commentaires ? count($this->commentaires) : 0;
    }

    // ========== PHOTOS ==========

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(?array $photos): static
    {
        $this->photos = $photos;
        return $this;
    }

    public function getMainPhoto(): ?string
    {
        return $this->mainPhoto;
    }

    public function setMainPhoto(?string $mainPhoto): static
    {
        $this->mainPhoto = $mainPhoto;
        return $this;
    }

    // ========== RELATIONS VOTES ==========

    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(Vote $vote): static
    {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setAvis($this);
        }
        return $this;
    }

    public function removeVote(Vote $vote): static
    {
        if ($this->votes->removeElement($vote)) {
            if ($vote->getAvis() === $this) {
                $vote->setAvis(null);
            }
        }
        return $this;
    }

    // ========== RELATIONS SIGNALEMENTS (NOUVEAU) ==========

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    public function addSignalement(Signalement $signalement): static
    {
        if (!$this->signalements->contains($signalement)) {
            $this->signalements->add($signalement);
            $signalement->setAvis($this);
        }
        return $this;
    }

    public function removeSignalement(Signalement $signalement): static
    {
        if ($this->signalements->removeElement($signalement)) {
            if ($signalement->getAvis() === $this) {
                $signalement->setAvis(null);
            }
        }
        return $this;
    }

    public function getNombreSignalements(): int
    {
        return $this->signalements->count();
    }

    public function hasSignalements(): bool
    {
        return $this->signalements->count() > 0;
    }

    // ========== MÉTHODE POUR AFFICHER LE STATUT EN TEXTE ==========
    
    public function getStatusText(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            default => 'Inconnu'
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }
}