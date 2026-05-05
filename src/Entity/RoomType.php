<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'room_types')]
class RoomType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'roomTypes')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Hotel $hotel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'max_occupancy', options: ['default' => 2])]
    private int $maxOccupancy = 2;

    #[ORM\Column(name: 'price_per_night', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $pricePerNight = null;

    #[ORM\Column(name: 'is_available', options: ['default' => true])]
    private bool $isAvailable = true;

    public function getId(): ?int { return $this->id; }
    public function getHotel(): ?Hotel { return $this->hotel; }
    public function setHotel(?Hotel $hotel): static { $this->hotel = $hotel; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getMaxOccupancy(): int { return $this->maxOccupancy; }
    public function setMaxOccupancy(int $maxOccupancy): static { $this->maxOccupancy = $maxOccupancy; return $this; }
    public function getPricePerNight(): ?string { return $this->pricePerNight; }
    public function setPricePerNight(string $pricePerNight): static { $this->pricePerNight = $pricePerNight; return $this; }
    public function isAvailable(): bool { return $this->isAvailable; }
    public function setIsAvailable(bool $isAvailable): static { $this->isAvailable = $isAvailable; return $this; }
}
