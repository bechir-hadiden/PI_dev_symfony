<?php

namespace App\Entity;

use App\Repository\VoyageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyages')]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'destination', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la destination est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'La destination doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La destination ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $destination = null;

    #[ORM\Column(name: 'dateDebut', type: 'date')]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date de début ne peut pas être dans le passé.'
    )]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'dateFin', type: 'date')]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être après la date de début.'
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'prix', type: 'float')]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif.')]
    #[Assert\LessThanOrEqual(
        value: 999999,
        message: 'Le prix ne peut pas dépasser {{ compared_value }} TND.'
    )]
    private ?float $prix = null;

    #[ORM\Column(name: 'imagePath', type: 'string', length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

<<<<<<< HEAD
    // 9. pays_depart — varchar(100) NULL
    #[ORM\Column(name: 'pays_depart', type: 'string', length: 100, nullable: true)]
=======
    #[ORM\Column(name: 'pays_depart', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le pays de départ doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le pays de départ ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s\-\,]+$/u',
        message: 'Le pays de départ ne peut contenir que des lettres, espaces et tirets.'
    )]
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
    private ?string $paysDepart = null;

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

    // ── Retourne uniquement la première image (séparateur ; ou |) ──
    public function getFirstImagePath(): ?string
    {
        if (!$this->imagePath) return null;

        $parts = preg_split('/[;|]/', $this->imagePath);
        foreach ($parts as $part) {
            $p = trim($part);
            if ($p !== '') return $p;
        }
        return null;
    }

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

    public function __toString(): string { return $this->destination ?? ''; }
}