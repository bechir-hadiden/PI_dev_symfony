<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiements')]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le montant est obligatoire")]
    #[Assert\Positive(message: "Le montant doit être supérieur à zéro")]
    private ?float $amount = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(choices: ["En attente", "Effectué", "Refusé", "bloqué"], message: "Statut invalide")]
    private ?string $status = null; // 'En attente', 'Effectué', 'Refusé', 'bloqué'

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "La méthode de paiement est obligatoire")]
    #[Assert\Choice(choices: ["Stripe", "Wallet"], message: "Méthode de paiement invalide")]
    private ?string $methodePaiement = null; // 'Stripe', 'Wallet'

    #[ORM\Column(nullable: true)]
    private ?int $reservationId = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(max: 15, maxMessage: "Le nom ne doit pas dépasser 15 caractères")]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/u", message: "Le nom ne doit contenir que des lettres")]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(max: 15, maxMessage: "Le prénom ne doit pas dépasser 15 caractères")]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/u", message: "Le prénom ne doit contenir que des lettres")]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse e-mail est obligatoire")]
    #[Assert\Email(message: "L'adresse email n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire")]
    #[Assert\Regex(pattern: "/^[0-9]{8}$/", message: "Le numéro de téléphone doit contenir exactement 8 chiffres")]
    private ?string $telephone = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class, inversedBy: 'paiements')]
    private ?Subscription $subscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\OneToOne(mappedBy: 'paiement', targetEntity: Facture::class, cascade: ['persist', 'remove'])]
    private ?Facture $facture = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $scoreRisque = null;

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

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

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

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getMethodePaiement(): ?string
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(string $methodePaiement): static
    {
        $this->methodePaiement = $methodePaiement;

        return $this;
    }

    public function getReservationId(): ?int
    {
        return $this->reservationId;
    }

    public function setReservationId(?int $reservationId): static
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        // unset the owning side of the relation if necessary
        if ($facture === null && $this->facture !== null) {
            $this->facture->setPaiement(null);
        }

        // set the owning side of the relation if necessary
        if ($facture !== null && $facture->getPaiement() !== $this) {
            $facture->setPaiement($this);
        }

        $this->facture = $facture;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): static
    {
        $this->attempts = $attempts;
        return $this;
    }

    public function incrementAttempts(): static
    {
        $this->attempts++;
        return $this;
    }
    public function getScoreRisque(): ?float
    {
        return $this->scoreRisque;
    }

    public function setScoreRisque(?float $scoreRisque): static
    {
        $this->scoreRisque = $scoreRisque;
        return $this;
    }
}
