<?php

namespace App\Entity;

use App\Repository\TransportTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransportTypeRepository::class)]
#[ORM\Table(name: 'transport_type')]
#[UniqueEntity(fields: ['nom'], message: 'Ce type de transport existe déjà.')]
class TransportType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idType', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le nom du type est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le nom ne doit contenir que des lettres, des espaces ou des tirets.')]
    private ?string $nom = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: 'Le prix de départ est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être positif.')]
    private ?float $prixDepart = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    /** @var Collection<int, Transport> */
    #[ORM\OneToMany(mappedBy: 'transportType', targetEntity: Transport::class, cascade: ['persist'], orphanRemoval: false)]
    private Collection $transports;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrixDepart(): ?float { return $this->prixDepart; }
    public function setPrixDepart(float $prixDepart): static { $this->prixDepart = $prixDepart; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    /** @return Collection<int, Transport> */
    public function getTransports(): Collection { return $this->transports; }

    public function addTransport(Transport $transport): static
    {
        if (!$this->transports->contains($transport)) {
            $this->transports->add($transport);
            $transport->setTransportType($this);
        }
        return $this;
    }

    public function removeTransport(Transport $transport): static
    {
        if ($this->transports->removeElement($transport)) {
            if ($transport->getTransportType() === $this) {
                $transport->setTransportType(null);
            }
        }
        return $this;
    }

    public function __toString(): string { return $this->nom ?? ''; }
}
