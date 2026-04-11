<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


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

    public function getId(): ?int
    {
        return $this->id;
    }



    // ─────────────────────────────
    // EMAIL
    // ─────────────────────────────

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }



    // utilisé par Symfony Security
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }



    // ─────────────────────────────
    // ROLES
    // ─────────────────────────────

    public function getRoles(): array
    {
        $roles = $this->roles;

        // rôle par défaut
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }



    // ─────────────────────────────
    // PASSWORD
    // ─────────────────────────────

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
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