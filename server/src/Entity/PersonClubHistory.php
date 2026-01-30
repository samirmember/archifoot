<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'person_club_history')]
class PersonClubHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'club_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Club $club = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(name: 'from_year', nullable: true)]
    private ?int $fromYear = null;

    #[ORM\Column(name: 'to_year', nullable: true)]
    private ?int $toYear = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;

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

    public function getFromYear(): ?int
    {
        return $this->fromYear;
    }

    public function setFromYear(?int $fromYear): static
    {
        $this->fromYear = $fromYear;

        return $this;
    }

    public function getToYear(): ?int
    {
        return $this->toYear;
    }

    public function setToYear(?int $toYear): static
    {
        $this->toYear = $toYear;

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
