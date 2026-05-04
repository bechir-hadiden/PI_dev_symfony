<?php

namespace App\Entity;

use App\Repository\TransportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransportRepository::class)]
#[ORM\Table(name: 'transport')]
#[UniqueEntity(
    fields: ['compagnie', 'numero'],
    message: 'Ce véhicule (même compagnie et numéro) existe déjà.'
)]
class Transport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La compagnie est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'La compagnie ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $compagnie = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de véhicule est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le numéro ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/^[A-Za-z0-9\s\-]+$/', message: 'Le numéro ne doit contenir que des lettres, chiffres, espaces et tirets.')]
    private ?string $numero = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'La capacité est obligatoire.')]
    #[Assert\Range(
        min: 1,
        max: 500,
        notInRangeMessage: 'La capacité doit être comprise entre {{ min }} et {{ max }} places.'
    )]
    private ?int $capacite = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix ne peut pas être négatif.')]
    private ?float $prix = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL de l\'image n\'est pas valide.')]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: TransportType::class, inversedBy: 'transports')]
    #[ORM\JoinColumn(name: 'transport_type_id', referencedColumnName: 'idType', nullable: false)]
    #[Assert\NotNull(message: 'Le type de transport est obligatoire.')]
    private ?TransportType $transportType = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $trajet = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'transports')]
    #[ORM\JoinColumn(name: 'vehicule_id', referencedColumnName: 'id', nullable: true)]
    private ?Vehicule $vehicule = null;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(mappedBy: 'transport', targetEntity: Reservation::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $reservations;

    /** @var Collection<int, ReservationTransport> */
    #[ORM\OneToMany(mappedBy: 'transport', targetEntity: ReservationTransport::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $reservationTransports;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->reservationTransports = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompagnie(): ?string { return $this->compagnie; }
    public function setCompagnie(string $compagnie): static { $this->compagnie = $compagnie; return $this; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getCapacite(): ?int { return $this->capacite; }
    public function setCapacite(int $capacite): static { $this->capacite = $capacite; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $prix): static { $this->prix = $prix; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getTransportType(): ?TransportType { return $this->transportType; }
    public function setTransportType(?TransportType $transportType): static { $this->transportType = $transportType; return $this; }

    public function getLocalisation(): ?string { return $this->trajet; } // Alias for backward compatibility if needed
    public function setLocalisation(?string $localisation): static { $this->trajet = $localisation; return $this; }

    public function getTrajet(): ?string { return $this->trajet; }
    public function setTrajet(?string $trajet): static { $this->trajet = $trajet; return $this; }

    public function getDateHeure(): ?\DateTimeInterface { return $this->dateHeure; }
    public function setDateHeure(?\DateTimeInterface $dateHeure): static { $this->dateHeure = $dateHeure; return $this; }

    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }

    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection { return $this->reservations; }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setTransport($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getTransport() === $this) {
                $reservation->setTransport(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s – %s', $this->compagnie ?? '', $this->numero ?? '');
    }

    /**
     * Calcule le nombre de places restantes disponibles
     */
    public function getPlacesDisponibles(): int
    {
        $placesReservees = 0;
        foreach ($this->reservations as $reservation) {
            if ($reservation->getStatut() !== Reservation::STATUT_ANNULEE) {
                $placesReservees += $reservation->getNombrePlaces();
            }
        }
        return max(0, ($this->capacite ?? 0) - $placesReservees);
    }
}
