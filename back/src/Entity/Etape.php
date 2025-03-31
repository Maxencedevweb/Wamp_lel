<?php

// src/Entity/Etape.php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EtapeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;



#[ApiResource(
    normalizationContext: ['groups' => ['etape:read']],
    denormalizationContext: ['groups' => ['etape:write']],
)]
#[ORM\Entity(repositoryClass: EtapeRepository::class)]
class Etape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etape:read', 'sequence:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etape:read', 'sequence:read'])]
    private ?string $nom = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
