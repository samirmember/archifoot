<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scoresheet_substitution')]
class ScoresheetSubstitution
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
    #[ORM\JoinColumn(name: 'player_out_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $playerOut = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'player_in_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $playerIn = null;

    #[ORM\Column(name: 'player_out_text', length: 200, nullable: true)]
    private ?string $playerOutText = null;

    #[ORM\Column(name: 'player_in_text', length: 200, nullable: true)]
    private ?string $playerInText = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $minute = null;

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

    public function getPlayerOut(): ?Player
    {
        return $this->playerOut;
    }

    public function setPlayerOut(?Player $playerOut): static
    {
        $this->playerOut = $playerOut;

        return $this;
    }

    public function getPlayerIn(): ?Player
    {
        return $this->playerIn;
    }

    public function setPlayerIn(?Player $playerIn): static
    {
        $this->playerIn = $playerIn;

        return $this;
    }

    public function getPlayerOutText(): ?string
    {
        return $this->playerOutText;
    }

    public function setPlayerOutText(?string $playerOutText): static
    {
        $this->playerOutText = $playerOutText;

        return $this;
    }

    public function getPlayerInText(): ?string
    {
        return $this->playerInText;
    }

    public function setPlayerInText(?string $playerInText): static
    {
        $this->playerInText = $playerInText;

        return $this;
    }

    public function getMinute(): ?string
    {
        return $this->minute;
    }

    public function setMinute(?string $minute): static
    {
        $this->minute = $minute;

        return $this;
    }
}
