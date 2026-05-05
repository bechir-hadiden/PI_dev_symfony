<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Username is required.')]
    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z][a-zA-Z0-9]*$/',
        message: 'Username must start with a letter and can only contain letters and numbers (e.g. "hamdi123").'
    )]
    private ?string $username = null;

    // Nullable: Google OAuth users have no local password
    #[ORM\Column(name: 'password_hash', length: 255, nullable: true)]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[ORM\Column(name: 'full_name', length: 100)]
    #[Assert\NotBlank(message: 'Full name is required.')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $fullName = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('ADMIN','CLIENT') NOT NULL DEFAULT 'CLIENT'")]
    private string $role = 'CLIENT';

    #[ORM\Column(name: 'created_at', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'is_blocked', options: ['default' => false])]
    private bool $isBlocked = false;

    #[ORM\Column(name: 'reset_otp', length: 255, nullable: true)]
    private ?string $resetOtp = null;

    #[ORM\Column(name: 'reset_otp_expires_at', nullable: true)]
    private ?\DateTimeImmutable $resetOtpExpiresAt = null;

    #[ORM\Column(name: 'google_id', length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    // ─── Lifecycle ────────────────────────────────────────────────────────────

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

    // ─── UserInterface ────────────────────────────────────────────────────────

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        return match ($this->role) {
            'ADMIN'  => ['ROLE_ADMIN', 'ROLE_USER'],
            default  => ['ROLE_CLIENT', 'ROLE_USER'],
        };
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    // ─── Getters / Setters ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    // Accept nullable — Google users have no password
    public function setPassword(?string $password): static { $this->password = $password; return $this; }

    public function getPlainPassword(): ?string { return $this->plainPassword; }
    public function setPlainPassword(?string $plainPassword): static { $this->plainPassword = $plainPassword; return $this; }

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(string $fullName): static { $this->fullName = $fullName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function isBlocked(): bool { return $this->isBlocked; }
    public function setIsBlocked(bool $isBlocked): static { $this->isBlocked = $isBlocked; return $this; }

    public function isAdmin(): bool { return $this->role === 'ADMIN'; }

    public function getResetOtp(): ?string { return $this->resetOtp; }
    public function setResetOtp(?string $resetOtp): static { $this->resetOtp = $resetOtp; return $this; }

    public function getResetOtpExpiresAt(): ?\DateTimeImmutable { return $this->resetOtpExpiresAt; }
    public function setResetOtpExpiresAt(?\DateTimeImmutable $v): static { $this->resetOtpExpiresAt = $v; return $this; }

    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $googleId): static { $this->googleId = $googleId; return $this; }

    public function isGoogleAccount(): bool { return $this->googleId !== null; }

    public function getAvatarUrl(): string
    {
        return $this->avatar
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->fullName ?? $this->username ?? 'U') . '&background=b8963e&color=fff';
    }
}
