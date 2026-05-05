<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// ════════════════════════════════════════════════════════════════════════════
//  HotelImage
// ════════════════════════════════════════════════════════════════════════════

#[ORM\Entity]
#[ORM\Table(name: 'hotel_images')]
class HotelImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Hotel $hotel = null;

    #[ORM\Column(name: 'image_url', length: 500)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'display_order', options: ['default' => 0])]
    private int $displayOrder = 0;

    public function getId(): ?int { return $this->id; }
    public function getHotel(): ?Hotel { return $this->hotel; }
    public function setHotel(?Hotel $hotel): static { $this->hotel = $hotel; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
    public function setDisplayOrder(int $displayOrder): static { $this->displayOrder = $displayOrder; return $this; }
}
