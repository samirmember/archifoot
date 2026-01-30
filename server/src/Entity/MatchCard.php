<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_card')]
class MatchCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'fixture_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Fixture $fixture = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $player = null;

    #[ORM\Column(name: 'player_text', length: 200, nullable: true)]
    private ?string $playerText = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $minute = null;

    #[ORM\Column(name: 'card_type', length: 5, nullable: true)]
    private ?string $cardType = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $reason = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFixture(): ?Fixture
    {
        return $this->fixture;
    }

    public function setFixture(?Fixture $fixture): static
    {
        $this->fixture = $fixture;

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

    public function getPlayerText(): ?string
    {
        return $this->playerText;
    }

    public function setPlayerText(?string $playerText): static
    {
        $this->playerText = $playerText;

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

    public function getCardType(): ?string
    {
        return $this->cardType;
    }

    public function setCardType(?string $cardType): static
    {
        $this->cardType = $cardType;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
