<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trophy_award_person')]
class TrophyAwardPerson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'trophy_award_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?TrophyAward $trophyAward = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Person $person = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrophyAward(): ?TrophyAward
    {
        return $this->trophyAward;
    }

    public function setTrophyAward(?TrophyAward $trophyAward): static
    {
        $this->trophyAward = $trophyAward;

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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }
}
