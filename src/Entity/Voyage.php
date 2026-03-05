<?php

namespace App\Entity;

use App\Repository\VoyageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyages')]
class Voyage
{
    // 1. id — int(11) NOT NULL AUTO_INCREMENT
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // 2. destination — varchar(255) NOT NULL
    #[ORM\Column(name: 'destination', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de destination est obligatoire')]
    private ?string $destination = null;

    // 3. dateDebut — date NOT NULL
    #[ORM\Column(name: 'dateDebut', type: 'date')]
    #[Assert\NotNull(message: 'La date de début est obligatoire')]
    private ?\DateTimeInterface $dateDebut = null;

    // 4. dateFin — date NOT NULL
    #[ORM\Column(name: 'dateFin', type: 'date')]
    #[Assert\NotNull(message: 'La date de fin est obligatoire')]
    private ?\DateTimeInterface $dateFin = null;

    // 5. prix — double NOT NULL
    #[ORM\Column(name: 'prix', type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    private ?float $prix = null;

    // 6. imagePath — varchar(255) NULL
    #[ORM\Column(name: 'imagePath', type: 'string', length: 255, nullable: true)]
    private ?string $imagePath = null;

    // 7. description — text NULL
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    // 9. pays_depart — varchar(100) NULL
    #[ORM\Column(name: 'pavs_depart', type: 'string', length: 100, nullable: true)]
    private ?string $paysDepart = null;

    // 8. destination_id — int(11) NULL (FK vers destination)
    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'voyages')]
    #[ORM\JoinColumn(name: 'destination_id', referencedColumnName: 'id', nullable: true)]
    private ?Destination $destinationRel = null;

    // ── Getters / Setters ─────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getDestination(): ?string { return $this->destination; }
    public function setDestination(string $v): static { $this->destination = $v; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $v): static { $this->dateDebut = $v; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $v): static { $this->dateFin = $v; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $v): static { $this->prix = $v; return $this; }

    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $v): static { $this->imagePath = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    public function getPaysDepart(): ?string { return $this->paysDepart; }
    public function setPaysDepart(?string $v): static { $this->paysDepart = $v; return $this; }

    public function getDestinationRel(): ?Destination { return $this->destinationRel; }
    public function setDestinationRel(?Destination $v): static { $this->destinationRel = $v; return $this; }

    public function getDureeJours(): int
    {
        if (!$this->dateDebut || !$this->dateFin) return 0;
        return (int) $this->dateDebut->diff($this->dateFin)->days;
    }

    public function __toString(): string
    {
        return $this->destination ?? '';
    }
}