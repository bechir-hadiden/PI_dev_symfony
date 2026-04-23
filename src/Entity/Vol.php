<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "vols")]
class Vol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "bigint")]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $arrivee = null;

    #[ORM\Column(type: "float")]
    private ?float $prix = null;

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getArrivee(): ?string { return $this->arrivee; }
    public function getPrix(): ?float { return (float) $this->prix; }
}