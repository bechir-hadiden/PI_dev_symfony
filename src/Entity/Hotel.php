<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "hotels")] // Nom exact de la table dans ta capture
class Hotel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\Column(name: "price_per_night", type: "decimal", precision: 10, scale: 2)]
    private ?string $pricePerNight = null;

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function getPricePerNight(): ?float { return (float) $this->pricePerNight; }
}