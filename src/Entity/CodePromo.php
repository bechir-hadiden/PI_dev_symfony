<?php

namespace App\Entity;

use App\Repository\CodePromoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CodePromoRepository::class)]
#[ORM\Table(name: "code_promo")]
class CodePromo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_code")]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 20)]
    private ?string $code_texte = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $date_expiration = null;

    #[ORM\ManyToOne(targetEntity: Offre::class)]
    #[ORM\JoinColumn(name: "id_offre", referencedColumnName: "id_offre", nullable: false)]
    private ?Offre $offre = null;

    // Getters and Setters ...
    public function getId(): ?int { return $this->id; }
    public function getCodeTexte(): ?string { return $this->code_texte; }
    public function setCodeTexte(string $code_texte): self { $this->code_texte = $code_texte; return $this; }
    public function getDateExpiration(): ?\DateTimeInterface { return $this->date_expiration; }
    public function setDateExpiration(\DateTimeInterface $date_expiration): self { $this->date_expiration = $date_expiration; return $this; }
    public function getOffre(): ?Offre { return $this->offre; }
    public function setOffre(?Offre $offre): self { $this->offre = $offre; return $this; }
}