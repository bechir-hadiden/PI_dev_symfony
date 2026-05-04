<?php

namespace App\Entity;

use App\Repository\DestinationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DestinationRepository::class)]
#[ORM\Table(name: 'destination')]
class Destination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la destination est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s\-\'\,\.]+$/u',
        message: 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.'
    )]
    private ?string $nom = null;

    #[ORM\Column(name: 'pays', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le pays doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s\-]+$/u',
        message: 'Le pays ne peut contenir que des lettres et des tirets.'
    )]
    private ?string $pays = null;

    #[ORM\Column(name: 'code_iata', type: 'string', length: 3, nullable: true)]
    #[Assert\Length(
        exactly: 3,
        exactMessage: 'Le code IATA doit contenir exactement {{ limit }} lettres (ex: CDG, TUN).'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z]{3}$/',
        message: 'Le code IATA doit contenir 3 lettres majuscules uniquement (ex: CDG, TUN, DJE).'
    )]
    private ?string $codeIata = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    // Toutes les images séparées par "|"
    #[ORM\Column(name: 'image_url', type: 'text', nullable: true)]  // text = illimité
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'video_url', type: 'string', length: 500, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'L\'URL de la vidéo ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_\-]{11}$/',
        message: 'Entrez uniquement l\'ID YouTube (11 caractères, ex: dQw4w9WgXcQ), pas l\'URL complète.'
    )]
    private ?string $videoUrl = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: '`order`', type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: 'L\'ordre doit être un nombre positif ou zéro.')]
    private ?int $order = null;

    #[ORM\OneToMany(mappedBy: 'destinationRel', targetEntity: Voyage::class)]
    private Collection $voyages;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->voyages      = new ArrayCollection();
    }

    // ── Retourne le tableau de toutes les images ──────────────────
    public function getAllImages(): array
    {
        if (!$this->imageUrl) return [];

        $raw = trim($this->imageUrl);

        // Format JSON (stocké par n8n) : ["https://...","https://..."]
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? array_filter($decoded) : [];
        }

        // Format pipe (url1|url2|url3)
        return array_filter(
            explode('|', $raw),
            fn($img) => trim($img) !== ''
        );
    }

    // ── Retourne la première image uniquement ─────────────────────
    public function getFirstImage(): ?string
    {
        $images = $this->getAllImages();
        return !empty($images) ? array_values($images)[0] : null;
    }

    // ── Sauvegarde un tableau d'images en string séparé par | ─────
    public function setAllImages(array $images): static
    {
        $filtered = array_filter($images, fn($img) => trim($img) !== '');
        $this->imageUrl = implode('|', array_values($filtered)) ?: null;
        return $this;
    }

    // ── Ajoute une image à la liste existante ─────────────────────
    public function addImage(string $imagePath): static
    {
        $images = $this->getAllImages();
        $images[] = $imagePath;
        return $this->setAllImages($images);
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $v): static { $this->nom = $v; return $this; }

    public function getPays(): ?string { return $this->pays; }
    public function setPays(string $v): static { $this->pays = $v; return $this; }

    public function getCodeIata(): ?string { return $this->codeIata; }
    public function setCodeIata(?string $v): static { $this->codeIata = $v ? strtoupper(trim($v)) : null; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $v): static { $this->imageUrl = $v; return $this; }

    public function getVideoUrl(): ?string { return $this->videoUrl; }
    public function setVideoUrl(?string $v): static { $this->videoUrl = $v; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $v): static { $this->dateCreation = $v; return $this; }

    public function getOrder(): ?int { return $this->order; }
    public function setOrder(?int $v): static { $this->order = $v; return $this; }

    public function getVoyages(): Collection { return $this->voyages; }

    public function addVoyage(Voyage $voyage): static
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages->add($voyage);
            $voyage->setDestinationRel($this);
        }
        return $this;
    }

    public function removeVoyage(Voyage $voyage): static
    {
        if ($this->voyages->removeElement($voyage) && $voyage->getDestinationRel() === $this) {
            $voyage->setDestinationRel(null);
        }
        return $this;
    }

    public function __toString(): string { return $this->nom . ' — ' . $this->pays; }
}