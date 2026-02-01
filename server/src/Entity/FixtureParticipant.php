<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity]
#[ORM\Table(name: 'fixture_participant')]
#[ORM\UniqueConstraint(name: 'uq_fixture_participant_fixture_id_role', columns: ['fixture_id', 'role'])]
#[ORM\Index(name: 'ix_fixture_participant_team_id', columns: ['team_id'])]
#[ORM\Index(name: 'ix_fixture_participant_fixture_id', columns: ['fixture_id'])]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'fixture' => 'exact',
])]
class FixtureParticipant
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

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    #[ORM\Column(name: 'score_extra', nullable: true)]
    private ?int $scoreExtra = null;

    #[ORM\Column(name: 'score_penalty', nullable: true)]
    private ?int $scorePenalty = null;

    #[ORM\Column(name: 'is_winner', nullable: true)]
    private ?bool $isWinner = null;

    #[ORM\Column(name: 'venue_role', length: 10, nullable: true)]
    private ?string $venueRole = null;

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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getScoreExtra(): ?int
    {
        return $this->scoreExtra;
    }

    public function setScoreExtra(?int $scoreExtra): static
    {
        $this->scoreExtra = $scoreExtra;

        return $this;
    }

    public function getScorePenalty(): ?int
    {
        return $this->scorePenalty;
    }

    public function setScorePenalty(?int $scorePenalty): static
    {
        $this->scorePenalty = $scorePenalty;

        return $this;
    }

    public function isWinner(): ?bool
    {
        return $this->isWinner;
    }

    public function setIsWinner(?bool $isWinner): static
    {
        $this->isWinner = $isWinner;

        return $this;
    }

    public function getVenueRole(): ?string
    {
        return $this->venueRole;
    }

    public function setVenueRole(?string $venueRole): static
    {
        $this->venueRole = $venueRole;

        return $this;
    }
}
