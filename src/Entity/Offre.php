<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\Table(name: "offre")]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_offre")]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Le titre ne peut pas être vide")]
    #[Assert\Length(min: 5, minMessage: "Le titre doit faire au moins {{ limit }} caractères")]
    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(
        min: 10, 
        minMessage: "La description doit contenir au moins {{ limit }} caractères pour être informative."
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Assert\NotBlank(message: "La remise est obligatoire")]
    #[Assert\Range(min: 1, max: 100, notInRangeMessage: "La remise doit être entre 1% et 100%")]
    #[ORM\Column(name: "taux_remise")]
    private ?int $tauxRemise = null;

    #[ORM\Column(name: "date_debut", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\GreaterThan(propertyPath: "dateDebut", message: "La date de fin doit être après la date de début")]
    #[ORM\Column(name: "date_fin", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(name: "is_local_support")]
    private ?bool $isLocalSupport = null;

    #[ORM\Column(name: "image_url", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    // --- PROPRIÉTÉS VIRTUELLES (Pour l'intégration Service/SQL) ---
    private ?string $destination = null;
    private ?float $prixInitial = null;

    // --- RELATIONS D'INTÉGRATION ---

    #[ORM\ManyToOne(targetEntity: Voyage::class)]
    #[ORM\JoinColumn(name: "id_voyage", referencedColumnName: "id", nullable: true)]
    private ?Voyage $voyage = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(name: "id_hotel", referencedColumnName: "id", nullable: true)]
    private ?Hotel $hotel = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class)]
    #[ORM\JoinColumn(name: "id_vehicule", referencedColumnName: "idVehicule", nullable: true)]
    private ?Vehicule $vehicule = null;

    #[ORM\ManyToOne(targetEntity: Vol::class)]
    #[ORM\JoinColumn(name: "id_vol", referencedColumnName: "id", nullable: true)]
    private ?Vol $vol = null;


    // --- GETTERS ET SETTERS ---

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self{ $this->id = $id; return $this;}

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getTauxRemise(): ?int { return $this->tauxRemise; }
    public function setTauxRemise(int $tauxRemise): self { $this->tauxRemise = $tauxRemise; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }

    public function isLocalSupport(): ?bool { return $this->isLocalSupport; }
    public function setIsLocalSupport(bool $isLocalSupport): self { $this->isLocalSupport = $isLocalSupport; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $image_url): self { $this->imageUrl = $image_url; return $this; }

    public function getVoyage(): ?Voyage { return $this->voyage; }
    public function setVoyage(?Voyage $voyage): self { $this->voyage = $voyage; return $this; }

    public function getHotel(): ?Hotel { return $this->hotel; }
    public function setHotel(?Hotel $hotel): self { $this->hotel = $hotel; return $this; }

    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): self { $this->vehicule = $vehicule; return $this; }

    public function getVol(): ?Vol { return $this->vol; }
    public function setVol(?Vol $vol): self { $this->vol = $vol; return $this; }

    // --- NOUVEAUX SETTERS POUR LE SERVICE ---

    public function setDestination(?string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function setPrixInitial(?float $prixInitial): self
    {
        $this->prixInitial = $prixInitial;
        return $this;
    }

    // --- MÉTHODES MÉTIER (Utilisées par Twig) ---

    /**
     * Retourne le nom de la destination/partenaire
     */
    public function getLabelDestination(): string
    {
        // Si le service a déjà injecté le nom via SQL
        if ($this->destination) {
            return $this->destination;
        }

        // Sinon, logique de secours (Fallback)
        if ($this->category === 'VOL' && $this->vol) {
            return "Vers " . $this->vol->getArrivee();
        }
        if ($this->category === 'HOTEL' && $this->hotel) {
            return $this->hotel->getName();
        }
        if ($this->category === 'TRANSPORT' && $this->vehicule) {
            return $this->vehicule->getType() . " (" . $this->vehicule->getVille() . ")";
        }
        if ($this->voyage) {
            return $this->voyage->getDestination();
        }
        return "Destination non spécifiée";
    }

    /**
     * Récupère le prix d'origine
     */
    public function getPrixInitial(): float
    {
        // Si le service a injecté le prix
        if ($this->prixInitial !== null) {
            return $this->prixInitial;
        }

        // Sinon, calcul manuel via relations
        if ($this->category === 'VOL' && $this->vol) {
            return (float) $this->vol->getPrix();
        }
        if ($this->category === 'HOTEL' && $this->hotel) {
            return (float) $this->hotel->getPricePerNight();
        }
        if ($this->category === 'TRANSPORT' && $this->vehicule) {
            return (float) $this->vehicule->getPrix();
        }
        if ($this->voyage) {
            return (float) $this->voyage->getPrix();
        }
        return 0.0;
    }

    /**
     * Calcule le prix après remise
     */
    public function getPrixFinal(): float
    {
        $prixInitial = $this->getPrixInitial();
        if ($prixInitial > 0) {
            return $prixInitial - ($prixInitial * ($this->tauxRemise / 100));
        }
        return 0.0;
    }
}