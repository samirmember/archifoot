<?php

namespace App\Entity;

use App\Repository\PersonAssignmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonAssignmentRepository::class)]
#[ORM\Table(name: 'person_assignment')]
#[ORM\UniqueConstraint(
    name: 'uq_tpr_exact',
    columns: ['person_id','team_id','role_id','season_id','from_date','to_date']
)]
#[ORM\Index(name: 'ix_tpr_role_dates', columns: ['team_id','role_id','from_date','to_date'])]
#[ORM\Index(name: 'ix_tpr_person_dates', columns: ['person_id','from_date','to_date'])]
#[ORM\Index(name: 'ix_tpr_season', columns: ['season_id'])]
class PersonAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(optional: false)]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Person $person;

    #[ORM\ManyToOne(optional: false)]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Team $team;

    #[ORM\ManyToOne(optional: false)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Role $role;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Season $season = null;

    #[ORM\Column(name: 'from_date', type: 'date', nullable: true)]
    private ?\DateTimeImmutable $fromDate = null;

    #[ORM\Column(name: 'to_date', type: 'date', nullable: true)]
    private ?\DateTimeImmutable $toDate = null;

    public function getId(): ?int { return $this->id; }

    public function getPerson(): Person { return $this->person; }
    public function setPerson(Person $person): self { $this->person = $person; return $this; }

    public function getTeam(): Team { return $this->team; }
    public function setTeam(Team $team): self { $this->team = $team; return $this; }

    public function getRole(): Role { return $this->role; }
    public function setRole(Role $role): self { $this->role = $role; return $this; }

    public function getSeason(): ?Season { return $this->season; }
    public function setSeason(?Season $season): self { $this->season = $season; return $this; }

    public function getFromDate(): ?\DateTimeImmutable { return $this->fromDate; }
    public function setFromDate(?\DateTimeImmutable $fromDate): self { $this->fromDate = $fromDate; return $this; }

    public function getToDate(): ?\DateTimeImmutable { return $this->toDate; }
    public function setToDate(?\DateTimeImmutable $toDate): self { $this->toDate = $toDate; return $this; }
}