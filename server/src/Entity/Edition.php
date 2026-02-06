<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity]
#[ORM\Table(name: 'edition')]
#[ORM\UniqueConstraint(name: 'uq_edition_competition_id_season_id_name', columns: ['competition_id', 'season_id', 'name'])]
class Edition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Owning side (la FK est dans edition.competition_id)
     */
    #[ORM\ManyToOne(targetEntity: Competition::class, inversedBy: 'editions', fetch: 'LAZY')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Competition $competition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Season $season = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'division_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Division $division = null;

    /**
     * Inverse side du ManyToMany Fixture<->Edition
     */
    #[ORM\ManyToMany(
        targetEntity: Fixture::class,
        mappedBy: 'editions',
        fetch: 'EXTRA_LAZY'
    )]
    #[Ignore]
    private Collection $fixtures;

    public function __construct()
    {
        $this->fixtures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompetition(): ?Competition
    {
        return $this->competition;
    }

    public function setCompetition(?Competition $competition): self
    {
        $this->competition = $competition;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): static
    {
        $this->division = $division;

        return $this;
    }

    /** @return Collection<int, Fixture> */
    public function getFixtures(): Collection
    {
        return $this->fixtures;
    }

    public function addFixture(Fixture $fixture): self
    {
        if (!$this->fixtures->contains($fixture)) {
            $this->fixtures->add($fixture);
        }
        return $this;
    }

    public function removeFixture(Fixture $fixture): self
    {
        $this->fixtures->removeElement($fixture);
        return $this;
    }
}
