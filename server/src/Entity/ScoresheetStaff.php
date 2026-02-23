<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scoresheet_staff')]
class ScoresheetStaff
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
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Person $person = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $role = null;

    public function getId(): ?int { return $this->id; }
    public function getScoresheet(): ?Scoresheet { return $this->scoresheet; }
    public function setScoresheet(?Scoresheet $scoresheet): static { $this->scoresheet = $scoresheet; return $this; }
    public function getTeam(): ?Team { return $this->team; }
    public function setTeam(?Team $team): static { $this->team = $team; return $this; }
    public function getPerson(): ?Person { return $this->person; }
    public function setPerson(?Person $person): static { $this->person = $person; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role; return $this; }
}
