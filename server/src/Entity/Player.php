<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(mappedBy: 'player', targetEntity: PlayerTeamMembership::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['fromDate' => 'DESC'])]
    private Collection $clubMemberships;

    public function __construct()
    {
        $this->clubMemberships = new ArrayCollection();
    }

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

    public function getClubMemberships(): Collection
    {
        return $this->clubMemberships;
    }

    public function addClubMembership(PlayerTeamMembership $membership): self
    {
        if (!$this->clubMemberships->contains($membership)) {
            $this->clubMemberships->add($membership);
            $membership->setPlayer($this);
        }
        return $this;
    }

    public function removeClubMembership(PlayerTeamMembership $membership): self
    {
        if ($this->clubMemberships->removeElement($membership)) {
            if ($membership->getPlayer() === $this) {
                $membership->setPlayer(null);
            }
        }
        return $this;
    }

    /**
     * Club actuel (si tu appliques la règle is_current=1).
     */
    public function getCurrentClubMembership(): ?PlayerTeamMembership
    {
        foreach ($this->clubMemberships as $m) {
            if ($m->isCurrent()) {
                return $m;
            }
        }
        return null;
    }

    /**
     * Club à une date (match_date), sans requête DB additionnelle si la collection est hydratée.
     */
    public function getClubMembershipAt(\DateTimeInterface $date): ?PlayerTeamMembership
    {
        foreach ($this->clubMemberships as $m) {
            if ($m->covers($date)) {
                return $m;
            }
        }
        return null;
    }
}
