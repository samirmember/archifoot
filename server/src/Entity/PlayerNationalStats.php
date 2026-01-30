<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'player_national_stats')]
class PlayerNationalStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Player $player = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\Column(nullable: true)]
    private ?int $caps = null;

    #[ORM\Column(nullable: true)]
    private ?int $goals = null;

    #[ORM\Column(name: 'from_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $fromDate = null;

    #[ORM\Column(name: 'to_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $toDate = null;

    #[ORM\Column(name: 'source_note', length: 200, nullable: true)]
    private ?string $sourceNote = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getCaps(): ?int
    {
        return $this->caps;
    }

    public function setCaps(?int $caps): static
    {
        $this->caps = $caps;

        return $this;
    }

    public function getGoals(): ?int
    {
        return $this->goals;
    }

    public function setGoals(?int $goals): static
    {
        $this->goals = $goals;

        return $this;
    }

    public function getFromDate(): ?\DateTimeInterface
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTimeInterface $fromDate): static
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    public function getToDate(): ?\DateTimeInterface
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTimeInterface $toDate): static
    {
        $this->toDate = $toDate;

        return $this;
    }

    public function getSourceNote(): ?string
    {
        return $this->sourceNote;
    }

    public function setSourceNote(?string $sourceNote): static
    {
        $this->sourceNote = $sourceNote;

        return $this;
    }
}
