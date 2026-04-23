<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le plan d'abonnement est obligatoire")]
    #[Assert\Choice(choices: ["basic", "premium"], message: "Les plans autorisés sont 'basic' ou 'premium'")]
    private ?string $plan = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $price = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\GreaterThan(propertyPath: "startDate", message: "La date de fin doit être strictement après la date de début")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_CANCELLED])]
    private ?string $status = self::STATUS_ACTIVE;

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'subscription')]
    private Collection $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->startDate = new \DateTime();
        $this->status = self::STATUS_ACTIVE;
    }

    /* ── GETTERS & SETTERS ── */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(string $plan): static
    {
        $this->plan = $plan;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setSubscription($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getSubscription() === $this) {
                $paiement->setSubscription(null);
            }
        }

        return $this;
    }

    /* ── BUSINESS LOGIC ── */

    /**
     * Retourne true si l'abonnement est actif et non expiré.
     */
    public function isActive(): bool
    {
        $now = new \DateTime();
        return $this->status === self::STATUS_ACTIVE && ($this->endDate === null || $this->endDate > $now);
    }

    /**
     * Suspend l'abonnement.
     */
    public function suspend(): static
    {
        $this->status = self::STATUS_SUSPENDED;
        return $this;
    }

    /**
     * Active ou réactive l'abonnement.
     */
    public function activate(): static
    {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    /**
     * Annule l'abonnement.
     */
    public function cancel(): static
    {
        $this->status = self::STATUS_CANCELLED;
        return $this;
    }

    /**
     * Calcule le nombre de jours restants avant expiration.
     */
    public function getRemainingDays(): int
    {
        if (!$this->endDate) return 0;
        $now = new \DateTime();
        if ($now > $this->endDate) return 0;
        
        $diff = $now->diff($this->endDate);
        return (int)$diff->days;
    }

    /**
     * Calcule le pourcentage de progression de l'abonnement (de 0 à 100).
     */
    public function getProgressPercentage(): int
    {
        if (!$this->startDate || !$this->endDate) return 0;
        
        $total = $this->startDate->getTimestamp() - $this->endDate->getTimestamp();
        if ($total === 0) return 100;
        
        $now = new \DateTime();
        if ($now > $this->endDate) return 100;
        if ($now < $this->startDate) return 0;
        
        $consumed = $this->startDate->getTimestamp() - $now->getTimestamp();
        
        return (int)abs(($consumed / $total) * 100);
    }
}
