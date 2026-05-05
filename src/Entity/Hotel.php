<?php

namespace App\Entity;

use App\Repository\HotelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotelRepository::class)]
#[ORM\Table(name: 'hotels')]
#[ORM\HasLifecycleCallbacks]
class Hotel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Hotel name is required.')]
    #[Assert\Length(min: 2, max: 200)]
    private ?string $name = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Location is required.')]
    private ?string $location = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'City is required.')]
    private ?string $city = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Country is required.')]
    private ?string $country = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'price_per_night', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price per night is required.')]
    #[Assert\Positive(message: 'Price must be a positive number.')]
    private ?string $pricePerNight = null;

    #[ORM\Column(name: 'price_per_week', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $pricePerWeek = null;

    #[ORM\Column(type: 'decimal', precision: 2, scale: 1, options: ['default' => 0.0])]
    private ?string $rating = '0.0';

    #[ORM\Column(name: 'review_count', options: ['default' => 0])]
    private ?int $reviewCount = 0;

    #[ORM\Column(name: 'check_in_time', length: 10, nullable: true)]
    private ?string $checkInTime = null;

    #[ORM\Column(name: 'check_out_time', length: 10, nullable: true)]
    private ?string $checkOutTime = null;

    #[ORM\Column(name: 'contact_email', length: 100, nullable: true)]
    #[Assert\Email]
    private ?string $contactEmail = null;

    #[ORM\Column(name: 'contact_phone', length: 20, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(name: 'created_at', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, HotelImage>
     */
    #[ORM\OneToMany(targetEntity: HotelImage::class, mappedBy: 'hotel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $images;

    /**
     * @var Collection<int, HotelAmenity>
     */
    #[ORM\OneToMany(targetEntity: HotelAmenity::class, mappedBy: 'hotel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $amenities;

    /**
     * @var Collection<int, RoomType>
     */
    #[ORM\OneToMany(targetEntity: RoomType::class, mappedBy: 'hotel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $roomTypes;

    // Transient: uploaded files (not persisted as entity; handled by HotelService)
    private array $uploadedFiles = [];

    public function __construct()
    {
        $this->images    = new ArrayCollection();
        $this->amenities = new ArrayCollection();
        $this->roomTypes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Convenience helpers ──────────────────────────────────────────────────

    public function getMainImageUrl(): string
    {
        foreach ($this->images as $image) {
            return $image->getImageUrl();
        }
        return 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop';
    }

    public function getAmenityNames(): array
    {
        return $this->amenities->map(fn(HotelAmenity $a) => $a->getAmenityName())->toArray();
    }

    public function addAmenityByName(string $name): void
    {
        $amenity = new HotelAmenity();
        $amenity->setAmenityName(trim($name));
        $amenity->setHotel($this);
        $this->amenities->add($amenity);
    }

    // ─── Getters / Setters ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(string $location): static { $this->location = $location; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(string $city): static { $this->city = $city; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(string $country): static { $this->country = $country; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPricePerNight(): ?string { return $this->pricePerNight; }
    public function setPricePerNight(string $pricePerNight): static { $this->pricePerNight = $pricePerNight; return $this; }

    public function getPricePerWeek(): ?string { return $this->pricePerWeek; }
    public function setPricePerWeek(?string $pricePerWeek): static { $this->pricePerWeek = $pricePerWeek; return $this; }

    public function getRating(): ?string { return $this->rating; }
    public function setRating(?string $rating): static { $this->rating = $rating; return $this; }

    public function getReviewCount(): ?int { return $this->reviewCount; }
    public function setReviewCount(?int $reviewCount): static { $this->reviewCount = $reviewCount; return $this; }

    public function getCheckInTime(): ?string { return $this->checkInTime; }
    public function setCheckInTime(?string $checkInTime): static { $this->checkInTime = $checkInTime; return $this; }

    public function getCheckOutTime(): ?string { return $this->checkOutTime; }
    public function setCheckOutTime(?string $checkOutTime): static { $this->checkOutTime = $checkOutTime; return $this; }

    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $contactEmail): static { $this->contactEmail = $contactEmail; return $this; }

    public function getContactPhone(): ?string { return $this->contactPhone; }
    public function setContactPhone(?string $contactPhone): static { $this->contactPhone = $contactPhone; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getImages(): Collection { return $this->images; }
    public function addImage(HotelImage $image): static { $image->setHotel($this); $this->images[] = $image; return $this; }
    public function removeImage(HotelImage $image): static { $this->images->removeElement($image); return $this; }

    public function getAmenities(): Collection { return $this->amenities; }
    public function addAmenity(HotelAmenity $amenity): static { $amenity->setHotel($this); $this->amenities[] = $amenity; return $this; }
    public function removeAmenity(HotelAmenity $amenity): static { $this->amenities->removeElement($amenity); return $this; }

    public function getRoomTypes(): Collection { return $this->roomTypes; }
    public function addRoomType(RoomType $roomType): static { $roomType->setHotel($this); $this->roomTypes[] = $roomType; return $this; }
    public function removeRoomType(RoomType $roomType): static { $this->roomTypes->removeElement($roomType); return $this; }

    public function getUploadedFiles(): array { return $this->uploadedFiles; }
    public function setUploadedFiles(array $files): static { $this->uploadedFiles = $files; return $this; }
}
