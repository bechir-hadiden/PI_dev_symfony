<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "vehicule")] // Nom exact de la table
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idVehicule")] // Nom exact de la clé primaire
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 100)]
    private ?string $ville = null;

    #[ORM\Column(type: "float")]
    private ?float $prix = null;

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getType(): ?string { return $this->type; }
    public function getVille(): ?string { return $this->ville; }
    public function getPrix(): ?float { return $this->prix; }
}