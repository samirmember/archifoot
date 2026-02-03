<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity]
#[ORM\Table(name: 'team')]
#[ORM\UniqueConstraint(name: 'uq_team_team_type_club_id_national_team_id', columns: ['team_type', 'club_id', 'national_team_id'])]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'displayName' => 'partial',
])]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'team_type', length: 10, nullable: true)]
    private ?string $teamType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'club_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Club $club = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'national_team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?NationalTeam $nationalTeam = null;

    #[ORM\Column(name: 'display_name', length: 200, nullable: true)]
    private ?string $displayName = null;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: PlayerTeamMembership::class, orphanRemoval: true)]
    private Collection $playerClubMemberships;

    public function __construct()
    {
        $this->playerClubMemberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeamType(): ?string
    {
        return $this->teamType;
    }

    public function setTeamType(?string $teamType): static
    {
        $this->teamType = $teamType;

        return $this;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;

        return $this;
    }

    public function getNationalTeam(): ?NationalTeam
    {
        return $this->nationalTeam;
    }

    public function setNationalTeam(?NationalTeam $nationalTeam): static
    {
        $this->nationalTeam = $nationalTeam;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getPlayerTeamMemberships(): Collection
    {
        return $this->playerClubMemberships;
    }
}
