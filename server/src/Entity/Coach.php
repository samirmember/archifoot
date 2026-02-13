<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'coach')]
class Coach
{
    const ROLES = [
        'Head' => 'Entraîneur principal',
        'Assistant' => 'Entraîneur assistant',
        'Trainer' => 'Entraîneur stagiaire'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(name: 'photo_url', length: 500, nullable: true)]
    private ?string $photoUrl = null;

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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    public function getPersonFullName(): ?string
    {
        return $this->person?->getFullName();
    }

    public function setPersonFullName(?string $fullName): static
    {
        if ($this->person === null && $fullName !== null && $fullName !== '') {
            $this->person = new Person();
        }

        if ($this->person !== null) {
            $this->person->setFullName($fullName);
        }

        return $this;
    }
}
