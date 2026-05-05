<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'hotel_amenities')]
class HotelAmenity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'amenities')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Hotel $hotel = null;

    #[ORM\Column(name: 'amenity_name', length: 100)]
    private ?string $amenityName = null;

    public function getId(): ?int { return $this->id; }
    public function getHotel(): ?Hotel { return $this->hotel; }
    public function setHotel(?Hotel $hotel): static { $this->hotel = $hotel; return $this; }
    public function getAmenityName(): ?string { return $this->amenityName; }
    public function setAmenityName(string $amenityName): static { $this->amenityName = $amenityName; return $this; }
}
