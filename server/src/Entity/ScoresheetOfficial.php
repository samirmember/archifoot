<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scoresheet_official')]
class ScoresheetOfficial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'scoresheet_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Scoresheet $scoresheet = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $role = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Person $person = null;

    #[ORM\Column(name: 'name_text', length: 150, nullable: true)]
    private ?string $nameText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScoresheet(): ?Scoresheet
    {
        return $this->scoresheet;
    }

    public function setScoresheet(?Scoresheet $scoresheet): static
    {
        $this->scoresheet = $scoresheet;

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

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }

    public function getNameText(): ?string
    {
        return $this->nameText;
    }

    public function setNameText(?string $nameText): static
    {
        $this->nameText = $nameText;

        return $this;
    }
}
