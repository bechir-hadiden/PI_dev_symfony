<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    // ========== CONSTANTES DE STATUT ==========
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nomClient = null;

    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 100)]
    private ?string $destination = null;

    #[ORM\Column(length: 50)]
    private ?string $airline = null;

    #[ORM\Column(length: 20)]
    private ?string $flightNumber = null;

    #[ORM\Column(length: 50)]
    private ?string $departureAirport = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $departureTime = null;

    #[ORM\Column(length: 50)]
    private ?string $arrivalAirport = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $arrivalTime = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $numberOfPassengers = 1;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $reservationDate = null;

    // ========== STATUT (MODIFIÉ POUR ADMIN) ==========
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'pending'])]
    private ?string $status = self::STATUS_PENDING;

    // ========== NOUVEAUX CHAMPS POUR BILLET ÉLECTRONIQUE ==========
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $seatNumber = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $boardingPassFile = null;

    #[ORM\Column(type: 'boolean')]
    private bool $boardingPassSent = false;

    // ========== NOUVEAUX CHAMPS POUR ADMIN ==========
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $confirmedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    // ========== CONSTRUCTEUR ==========
    public function __construct()
    {
        $this->reservationDate = new \DateTime();
        $this->boardingPassSent = false;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;
        return $this;
    }

    public function getAirline(): ?string
    {
        return $this->airline;
    }

    public function setAirline(string $airline): static
    {
        $this->airline = $airline;
        return $this;
    }

    public function getFlightNumber(): ?string
    {
        return $this->flightNumber;
    }

    public function setFlightNumber(string $flightNumber): static
    {
        $this->flightNumber = $flightNumber;
        return $this;
    }

    public function getDepartureAirport(): ?string
    {
        return $this->departureAirport;
    }

    public function setDepartureAirport(string $departureAirport): static
    {
        $this->departureAirport = $departureAirport;
        return $this;
    }

    public function getDepartureTime(): ?\DateTimeInterface
    {
        return $this->departureTime;
    }

    public function setDepartureTime(\DateTimeInterface $departureTime): static
    {
        $this->departureTime = $departureTime;
        return $this;
    }

    public function getArrivalAirport(): ?string
    {
        return $this->arrivalAirport;
    }

    public function setArrivalAirport(string $arrivalAirport): static
    {
        $this->arrivalAirport = $arrivalAirport;
        return $this;
    }

    public function getArrivalTime(): ?\DateTimeInterface
    {
        return $this->arrivalTime;
    }

    public function setArrivalTime(\DateTimeInterface $arrivalTime): static
    {
        $this->arrivalTime = $arrivalTime;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getNumberOfPassengers(): ?int
    {
        return $this->numberOfPassengers;
    }

    public function setNumberOfPassengers(int $numberOfPassengers): static
    {
        $this->numberOfPassengers = $numberOfPassengers;
        return $this;
    }

    public function getReservationDate(): ?\DateTimeInterface
    {
        return $this->reservationDate;
    }

    public function setReservationDate(\DateTimeInterface $reservationDate): static
    {
        $this->reservationDate = $reservationDate;
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
        
        // Met à jour les dates en fonction du statut
        if ($status === self::STATUS_CONFIRMED && $this->confirmedAt === null) {
            $this->confirmedAt = new \DateTime();
        } elseif ($status === self::STATUS_CANCELLED && $this->cancelledAt === null) {
            $this->cancelledAt = new \DateTime();
        }
        
        return $this;
    }

    // ========== MÉTHODES UTILITAIRES POUR LE STATUT ==========

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function confirm(): static
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmedAt = new \DateTime();
        return $this;
    }

    public function cancel(?string $reason = null): static
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTime();
        $this->cancellationReason = $reason;
        return $this;
    }

    public function complete(): static
    {
        $this->status = self::STATUS_COMPLETED;
        return $this;
    }

    // ========== GETTERS/SETTERS POUR BILLET ÉLECTRONIQUE ==========

    public function getSeatNumber(): ?string
    {
        return $this->seatNumber;
    }

    public function setSeatNumber(?string $seatNumber): static
    {
        $this->seatNumber = $seatNumber;
        return $this;
    }

    public function getBoardingPassFile(): ?string
    {
        return $this->boardingPassFile;
    }

    public function setBoardingPassFile(?string $boardingPassFile): static
    {
        $this->boardingPassFile = $boardingPassFile;
        return $this;
    }

    public function isBoardingPassSent(): bool
    {
        return $this->boardingPassSent;
    }

    public function setBoardingPassSent(bool $boardingPassSent): static
    {
        $this->boardingPassSent = $boardingPassSent;
        return $this;
    }

    // ========== GETTERS/SETTERS POUR ADMIN ==========

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeInterface
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeInterface $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;
        return $this;
    }

    // ========== MÉTHODES UTILITAIRES ==========

    /**
     * Calcule le prix total (prix × nombre de passagers)
     */
    public function getTotalPrice(): float
    {
        return $this->price * $this->numberOfPassengers;
    }

    /**
     * Vérifie si la réservation peut être annulée (plus de 48h avant le départ)
     */
    public function isCancellable(): bool
    {
        // Une réservation déjà annulée ou complétée ne peut pas être annulée
        if ($this->isCancelled() || $this->isCompleted()) {
            return false;
        }
        
        $now = new \DateTime();
        $departure = $this->departureTime;
        $interval = $now->diff($departure);
        
        // Annulable si départ dans plus de 2 jours (48h) et non confirmée
        return $interval->days >= 2 && !$this->isConfirmed();
    }

    /**
     * Vérifie si la réservation peut être confirmée
     */
    public function isConfirmable(): bool
    {
        return $this->isPending() && $this->departureTime > new \DateTime();
    }

    /**
     * Retourne le statut formaté pour l'affichage
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_COMPLETED => 'Terminé',
            default => $this->status
        };
    }

    /**
     * Retourne la classe CSS pour le statut
     */
    public function getStatusClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_COMPLETED => 'info',
            default => 'secondary'
        };
    }

    /**
     * Retourne l'icône FontAwesome pour le statut
     */
    public function getStatusIcon(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'fa-clock',
            self::STATUS_CONFIRMED => 'fa-check-circle',
            self::STATUS_CANCELLED => 'fa-times-circle',
            self::STATUS_COMPLETED => 'fa-flag-checkered',
            default => 'fa-question-circle'
        };
    }

    /**
     * Retourne le numéro de réservation formaté
     */
    public function getReservationNumber(): string
    {
        return 'SM-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Retourne la durée du vol (formatée)
     */
    public function getDuration(): string
    {
        $diff = $this->departureTime->diff($this->arrivalTime);
        $hours = $diff->h;
        $minutes = $diff->i;
        
        if ($hours > 0) {
            return $hours . 'h' . ($minutes > 0 ? $minutes . 'min' : '');
        }
        return $minutes . 'min';
    }

    /**
     * Retourne le numéro de siège formaté
     */
    public function getFormattedSeatNumber(): string
    {
        if (!$this->seatNumber) {
            return 'À définir à l\'enregistrement';
        }
        return $this->seatNumber;
    }

    /**
     * Vérifie si le billet électronique a été généré
     */
    public function hasBoardingPass(): bool
    {
        return $this->boardingPassFile !== null && $this->boardingPassSent === true;
    }

    /**
     * Retourne l'URL du billet électronique
     */
    public function getBoardingPassUrl(): ?string
    {
        if (!$this->boardingPassFile) {
            return null;
        }
        return '/uploads/boarding_passes/' . $this->boardingPassFile;
    }

    /**
     * Vérifie si le vol a déjà eu lieu
     */
    public function hasDeparted(): bool
    {
        return $this->departureTime < new \DateTime();
    }

    /**
     * Retourne le temps restant avant le départ
     */
    public function getTimeUntilDeparture(): ?string
    {
        if ($this->hasDeparted()) {
            return 'Vol déjà effectué';
        }
        
        $now = new \DateTime();
        $interval = $now->diff($this->departureTime);
        
        if ($interval->days > 0) {
            return $interval->days . ' jour(s)';
        }
        if ($interval->h > 0) {
            return $interval->h . ' heure(s)';
        }
        return $interval->i . ' minute(s)';
    }

    /**
     * Retourne la classe CSS pour le statut de départ
     */
    public function getDepartureStatusClass(): string
    {
        if ($this->hasDeparted()) {
            return 'secondary';
        }
        
        $now = new \DateTime();
        $interval = $now->diff($this->departureTime);
        
        if ($interval->days <= 1) {
            return 'warning';
        }
        return 'info';
    }
}