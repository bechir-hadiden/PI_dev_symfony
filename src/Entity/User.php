<?php

namespace App\Entity;

use App\Repository\UserRepository;
<<<<<<< HEAD
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
=======
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
<<<<<<< HEAD
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
=======
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?float $walletBalance = 0.0;

    #[ORM\Column]
    private ?int $loyaltyPoints = 0;

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'user')]
    private Collection $paiements;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $subscriptions;

    #[ORM\Column(length: 100, nullable: true, options: ['default' => 'Tunisie'])]
    private ?string $pays = 'Tunisie';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $estBloque = false;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }
=======

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;


    #[ORM\Column]
    private array $roles = [];


    #[ORM\Column]
    private ?string $password = null;


    #[ORM\Column(length: 255)]
    private ?string $name = null;


    #[ORM\Column(type: 'boolean')]
    private bool $faceRegistered = false;



    // ─────────────────────────────
    // ID
    // ─────────────────────────────
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
=======


    // ─────────────────────────────
    // EMAIL
    // ─────────────────────────────

>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
<<<<<<< HEAD

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
=======
        return $this;
    }



    // utilisé par Symfony Security
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

<<<<<<< HEAD
    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
=======


    // ─────────────────────────────
    // ROLES
    // ─────────────────────────────

    public function getRoles(): array
    {
        $roles = $this->roles;

        // rôle par défaut
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
<<<<<<< HEAD

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
=======
        return $this;
    }



    // ─────────────────────────────
    // PASSWORD
    // ─────────────────────────────

>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
<<<<<<< HEAD

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getWalletBalance(): ?float
    {
        return $this->walletBalance;
    }

    public function setWalletBalance(float $walletBalance): static
    {
        $this->walletBalance = $walletBalance;

        return $this;
    }

    public function getLoyaltyPoints(): ?int
    {
        return $this->loyaltyPoints;
    }

    public function setLoyaltyPoints(int $loyaltyPoints): static
    {
        $this->loyaltyPoints = $loyaltyPoints;

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
            $paiement->setUser($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getUser() === $this) {
                $paiement->setUser(null);
            }
        }

        return $this;
    }



    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;
        return $this;
    }

    public function isEstBloque(): bool
    {
        return $this->estBloque;
    }

    public function setEstBloque(bool $estBloque): static
    {
        $this->estBloque = $estBloque;
        return $this;
    }
}
=======
        return $this;
    }



    // ─────────────────────────────
    // NAME
    // ─────────────────────────────

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }



    // ─────────────────────────────
    // FACE REGISTERED
    // ─────────────────────────────

    public function isFaceRegistered(): bool
    {
        return $this->faceRegistered;
    }

    public function setFaceRegistered(bool $faceRegistered): static
    {
        $this->faceRegistered = $faceRegistered;
        return $this;
    }



    // ─────────────────────────────
    // SECURITY CLEANUP
    // ─────────────────────────────

    public function eraseCredentials(): void
    {
        // rien pour l'instant
    }
}
>>>>>>> 34a4e2a76d1d62f6523af667bd145de3bfcb305c
