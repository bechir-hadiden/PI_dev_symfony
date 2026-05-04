<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE  = 'confirmee';
    public const STATUT_ANNULEE    = 'annulee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    #[Assert\Positive(message: 'Le nombre de places doit être au moins 1.')]
    private int $nombrePlaces = 1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le nom du client est obligatoire.')]
    #[Assert\Length(min: 3, minMessage: 'Le nom doit faire au moins {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le nom ne doit contenir que des lettres.')]
    private ?string $nomClient = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas valide.")]
    private ?string $emailClient = null;

    #[ORM\ManyToOne(targetEntity: Transport::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', nullable: false)]
    private ?Transport $transport = null;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getDateReservation(): ?\DateTimeInterface { return $this->dateReservation; }
    public function setDateReservation(\DateTimeInterface $dateReservation): static { $this->dateReservation = $dateReservation; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getNombrePlaces(): int { return $this->nombrePlaces; }
    public function setNombrePlaces(int $nombrePlaces): static { $this->nombrePlaces = $nombrePlaces; return $this; }

    public function getNomClient(): ?string { return $this->nomClient; }
    public function setNomClient(?string $nomClient): static { $this->nomClient = $nomClient; return $this; }

    public function getEmailClient(): ?string { return $this->emailClient; }
    public function setEmailClient(?string $emailClient): static { $this->emailClient = $emailClient; return $this; }

    public function getTransport(): ?Transport { return $this->transport; }
    public function setTransport(?Transport $transport): static { $this->transport = $transport; return $this; }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_CONFIRMEE  => 'Confirmée',
            self::STATUT_ANNULEE    => 'Annulée',
            default                 => $this->statut,
        };
    }

    public function getMontantTotal(): float
    {
        return $this->transport ? $this->transport->getPrix() * $this->nombrePlaces : 0.0;
    }
}
