<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'division')]
class Division
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $level = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Country $country = null;

    #[ORM\Column(name: 'in_championship', nullable: true)]
    private ?bool $inChampionship = null;

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

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function isInChampionship(): ?bool
    {
        return $this->inChampionship;
    }

    public function setInChampionship(?bool $inChampionship): static
    {
        $this->inChampionship = $inChampionship;

        return $this;
    }
}
