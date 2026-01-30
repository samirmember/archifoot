<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_goal')]
#[ORM\Index(name: 'ix_match_goal_scorer_id', columns: ['scorer_id'])]
#[ORM\Index(name: 'ix_match_goal_fixture_id', columns: ['fixture_id'])]
class MatchGoal
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
    #[ORM\JoinColumn(name: 'scorer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $scorer = null;

    #[ORM\Column(name: 'scorer_text', length: 200, nullable: true)]
    private ?string $scorerText = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $minute = null;

    #[ORM\Column(name: 'goal_type', length: 20, nullable: true)]
    private ?string $goalType = null;

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

    public function getScorer(): ?Player
    {
        return $this->scorer;
    }

    public function setScorer(?Player $scorer): static
    {
        $this->scorer = $scorer;

        return $this;
    }

    public function getScorerText(): ?string
    {
        return $this->scorerText;
    }

    public function setScorerText(?string $scorerText): static
    {
        $this->scorerText = $scorerText;

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

    public function getGoalType(): ?string
    {
        return $this->goalType;
    }

    public function setGoalType(?string $goalType): static
    {
        $this->goalType = $goalType;

        return $this;
    }
}
