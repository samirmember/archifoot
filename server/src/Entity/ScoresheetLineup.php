<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scoresheet_lineup')]
#[ORM\Index(name: 'ix_scoresheet_lineup_player_id', columns: ['player_id'])]
#[ORM\Index(name: 'ix_scoresheet_lineup_scoresheet_id', columns: ['scoresheet_id'])]
class ScoresheetLineup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'scoresheet_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Scoresheet $scoresheet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $player = null;

    #[ORM\Column(name: 'player_name_text', length: 200, nullable: true)]
    private ?string $playerNameText = null;

    #[ORM\Column(name: 'shirt_number', nullable: true)]
    private ?int $shirtNumber = null;

    #[ORM\Column(name: 'lineup_role', length: 12, nullable: true)]
    private ?string $lineupRole = null;

    #[ORM\Column(name: 'is_captain', nullable: true)]
    private ?bool $isCaptain = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Position $position = null;

    #[ORM\Column(name: 'sort_order', nullable: true)]
    private ?int $sortOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScoresheet(): ?Scoresheet
    {
        return $this->scoresheet;
    }

    public function setScoresheet(?Scoresheet $scoresheet): static
    {
        $this->scoresheet = $scoresheet;

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

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getPlayerNameText(): ?string
    {
        return $this->playerNameText;
    }

    public function setPlayerNameText(?string $playerNameText): static
    {
        $this->playerNameText = $playerNameText;

        return $this;
    }

    public function getShirtNumber(): ?int
    {
        return $this->shirtNumber;
    }

    public function setShirtNumber(?int $shirtNumber): static
    {
        $this->shirtNumber = $shirtNumber;

        return $this;
    }

    public function getLineupRole(): ?string
    {
        return $this->lineupRole;
    }

    public function setLineupRole(?string $lineupRole): static
    {
        $this->lineupRole = $lineupRole;

        return $this;
    }

    public function isCaptain(): ?bool
    {
        return $this->isCaptain;
    }

    public function setIsCaptain(?bool $isCaptain): static
    {
        $this->isCaptain = $isCaptain;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
