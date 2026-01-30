<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'edition')]
#[ORM\UniqueConstraint(name: 'uq_edition_competition_id_season_id_name', columns: ['competition_id', 'season_id', 'name'])]
class Edition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'competition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Competition $competition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Season $season = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'division_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Division $division = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
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

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): static
    {
        $this->division = $division;

        return $this;
    }
}
