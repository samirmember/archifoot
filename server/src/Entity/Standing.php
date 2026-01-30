<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'standing')]
#[ORM\Index(name: 'ix_standing_team_id', columns: ['team_id'])]
#[ORM\Index(name: 'ix_standing_edition_id', columns: ['edition_id'])]
class Standing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Edition $edition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'stage_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Stage $stage = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'matchday_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Matchday $matchday = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\Column(nullable: true)]
    private ?int $rank = null;

    #[ORM\Column(nullable: true)]
    private ?int $played = null;

    #[ORM\Column(nullable: true)]
    private ?int $won = null;

    #[ORM\Column(nullable: true)]
    private ?int $draw = null;

    #[ORM\Column(nullable: true)]
    private ?int $lost = null;

    #[ORM\Column(name: 'goals_for', nullable: true)]
    private ?int $goalsFor = null;

    #[ORM\Column(name: 'goals_against', nullable: true)]
    private ?int $goalsAgainst = null;

    #[ORM\Column(name: 'goal_diff', nullable: true)]
    private ?int $goalDiff = null;

    #[ORM\Column(nullable: true)]
    private ?int $points = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $observation = null;

    #[ORM\Column(name: 'source_note', length: 200, nullable: true)]
    private ?string $sourceNote = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function setStage(?Stage $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    public function getMatchday(): ?Matchday
    {
        return $this->matchday;
    }

    public function setMatchday(?Matchday $matchday): static
    {
        $this->matchday = $matchday;

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

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): static
    {
        $this->rank = $rank;

        return $this;
    }

    public function getPlayed(): ?int
    {
        return $this->played;
    }

    public function setPlayed(?int $played): static
    {
        $this->played = $played;

        return $this;
    }

    public function getWon(): ?int
    {
        return $this->won;
    }

    public function setWon(?int $won): static
    {
        $this->won = $won;

        return $this;
    }

    public function getDraw(): ?int
    {
        return $this->draw;
    }

    public function setDraw(?int $draw): static
    {
        $this->draw = $draw;

        return $this;
    }

    public function getLost(): ?int
    {
        return $this->lost;
    }

    public function setLost(?int $lost): static
    {
        $this->lost = $lost;

        return $this;
    }

    public function getGoalsFor(): ?int
    {
        return $this->goalsFor;
    }

    public function setGoalsFor(?int $goalsFor): static
    {
        $this->goalsFor = $goalsFor;

        return $this;
    }

    public function getGoalsAgainst(): ?int
    {
        return $this->goalsAgainst;
    }

    public function setGoalsAgainst(?int $goalsAgainst): static
    {
        $this->goalsAgainst = $goalsAgainst;

        return $this;
    }

    public function getGoalDiff(): ?int
    {
        return $this->goalDiff;
    }

    public function setGoalDiff(?int $goalDiff): static
    {
        $this->goalDiff = $goalDiff;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;

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
