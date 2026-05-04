<?php

namespace App\Entity;

use App\Repository\VoyageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: "voyages")] // 1. Changé voyage -> voyages
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id")] // 2. Changé id_voyage -> id
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $destination = null;

    #[ORM\Column(name: "dateDebut", type: Types::DATE_MUTABLE)] // 3. Nom exact SQL
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: "dateFin", type: Types::DATE_MUTABLE)] // 4. Nom exact SQL
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: "float")]
    private ?float $prix = null;

    // --- GETTERS ET SETTERS ---
    public function getId(): ?int { return $this->id; }

    public function getDestination(): ?string { return $this->destination; }
    public function setDestination(string $destination): self { $this->destination = $destination; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $prix): self { $this->prix = $prix; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }
}