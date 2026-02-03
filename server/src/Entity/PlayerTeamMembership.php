<?php

namespace App\Entity;

use App\Repository\PlayerTeamMembershipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerTeamMembershipRepository::class)]
#[ORM\Table(name: 'player_team_membership')]
#[ORM\UniqueConstraint(name: 'uq_pcm_exact', columns: ['player_id', 'team_id', 'from_date', 'to_date'])]
#[ORM\Index(name: 'ix_pcm_player_current', columns: ['player_id', 'is_current'])]
#[ORM\Index(name: 'ix_pcm_player_period', columns: ['player_id', 'from_date', 'to_date'])]
#[ORM\Index(name: 'ix_pcm_team_period', columns: ['team_id', 'from_date', 'to_date'])]
class PlayerTeamMembership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // FK player_id -> player.id (ON DELETE CASCADE)
    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'clubMemberships')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Player $player = null;

    // FK team_id -> team.id (doit être team_type='CLUB' côté DB via trigger/check)
    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'playerClubMemberships')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    #[ORM\Column(name: 'from_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fromDate = null;

    #[ORM\Column(name: 'to_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $toDate = null;

    #[ORM\Column(name: 'is_current', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isCurrent = false;

    #[ORM\Column(name: 'source_note', type: Types::STRING, length: 200, nullable: true)]
    private ?string $sourceNote = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): self
    {
        $this->player = $player;
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    public function getFromDate(): ?\DateTimeImmutable
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTimeImmutable $fromDate): self
    {
        $this->fromDate = $fromDate;
        return $this;
    }

    public function getToDate(): ?\DateTimeImmutable
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTimeImmutable $toDate): self
    {
        $this->toDate = $toDate;
        return $this;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    /**
     * Règle métier recommandée :
     * - si isCurrent = true => toDate doit être NULL
     * (On a déjà un trigger SQL qui force ça : ici on le reflète côté code)
     */
    public function setIsCurrent(bool $isCurrent): self
    {
        $this->isCurrent = $isCurrent;
        if ($isCurrent) {
            $this->toDate = null;
        }
        return $this;
    }

    public function getSourceNote(): ?string
    {
        return $this->sourceNote;
    }

    public function setSourceNote(?string $sourceNote): self
    {
        $this->sourceNote = $sourceNote;
        return $this;
    }

    /**
     * Helper : cette affectation couvre-t-elle une date donnée ?
     */
    public function covers(\DateTimeInterface $date): bool
    {
        $d = \DateTimeImmutable::createFromInterface($date);

        if ($this->fromDate !== null && $d < $this->fromDate) {
            return false;
        }
        if ($this->toDate !== null && $d > $this->toDate) {
            return false;
        }
        return true;
    }
}
