<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'player')]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'primary_position_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Position $primaryPosition = null;

    #[ORM\Column(name: 'preferred_foot', length: 10, nullable: true)]
    private ?string $preferredFoot = null;

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

    public function getPrimaryPosition(): ?Position
    {
        return $this->primaryPosition;
    }

    public function setPrimaryPosition(?Position $primaryPosition): static
    {
        $this->primaryPosition = $primaryPosition;

        return $this;
    }

    public function getPreferredFoot(): ?string
    {
        return $this->preferredFoot;
    }

    public function setPreferredFoot(?string $preferredFoot): static
    {
        $this->preferredFoot = $preferredFoot;

        return $this;
    }
}
