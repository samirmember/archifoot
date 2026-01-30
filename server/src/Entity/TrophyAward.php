<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trophy_award')]
#[ORM\Index(name: 'ix_trophy_award_team_id', columns: ['team_id'])]
#[ORM\Index(name: 'ix_trophy_award_person_id', columns: ['person_id'])]
#[ORM\Index(name: 'ix_trophy_award_season_id', columns: ['season_id'])]
class TrophyAward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'trophy_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Trophy $trophy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Season $season = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Edition $edition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Person $person = null;

    #[ORM\Column(nullable: true)]
    private ?int $rank = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrophy(): ?Trophy
    {
        return $this->trophy;
    }

    public function setTrophy(?Trophy $trophy): static
    {
        $this->trophy = $trophy;

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

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

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

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
