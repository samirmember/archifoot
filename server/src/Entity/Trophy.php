<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trophy')]
class Trophy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'competition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Competition $competition = null;

    #[ORM\Column(name: 'trophy_type', length: 30, nullable: true)]
    private ?string $trophyType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCompetition(): ?Competition
    {
        return $this->competition;
    }

    public function setCompetition(?Competition $competition): static
    {
        $this->competition = $competition;

        return $this;
    }

    public function getTrophyType(): ?string
    {
        return $this->trophyType;
    }

    public function setTrophyType(?string $trophyType): static
    {
        $this->trophyType = $trophyType;

        return $this;
    }
}
