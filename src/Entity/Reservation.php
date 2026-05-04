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

    /**
     * RELIANCE AVEC L'ENTITÉ DESTINATION
     * On remplace l'ancien champ string par une relation ManyToOne
     */
    #[ORM\ManyToOne(targetEntity: Destination::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Destination $destination = null;

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

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'pending'])]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $seatNumber = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $boardingPassFile = null;

    #[ORM\Column(type: 'boolean')]
    private bool $boardingPassSent = false;

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

    /**
     * Getter modifié pour retourner l'objet Destination
     */
    public function getDestination(): ?Destination
    {
        return $this->destination;
    }

    /**
     * Setter modifié pour accepter l'objet Destination
     */
    public function setDestination(?Destination $destination): static
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        if ($status === self::STATUS_CONFIRMED && $this->confirmedAt === null) {
            $this->confirmedAt = new \DateTime();
        } elseif ($status === self::STATUS_CANCELLED && $this->cancelledAt === null) {
            $this->cancelledAt = new \DateTime();
        }
        
        return $this;
    }

    // ========== MÉTHODES MÉTIER / UTILITAIRES ==========

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isConfirmed(): bool { return $this->status === self::STATUS_CONFIRMED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }

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

    public function getTotalPrice(): float
    {
        return $this->price * $this->numberOfPassengers;
    }

    public function getReservationNumber(): string
    {
        return 'SM-' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
    }

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

    // ========== GETTERS/SETTERS ADMIN & BILLET (Simplifiés) ==========

    public function getSeatNumber(): ?string { return $this->seatNumber; }
    public function setSeatNumber(?string $v): static { $this->seatNumber = $v; return $this; }

    public function getBoardingPassFile(): ?string { return $this->boardingPassFile; }
    public function setBoardingPassFile(?string $v): static { $this->boardingPassFile = $v; return $this; }

    public function isBoardingPassSent(): bool { return $this->boardingPassSent; }
    public function setBoardingPassSent(bool $v): static { $this->boardingPassSent = $v; return $this; }

    public function getAdminNotes(): ?string { return $this->adminNotes; }
    public function setAdminNotes(?string $v): static { $this->adminNotes = $v; return $this; }

    public function getConfirmedAt(): ?\DateTimeInterface { return $this->confirmedAt; }
    public function getCancelledAt(): ?\DateTimeInterface { return $this->cancelledAt; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }
}