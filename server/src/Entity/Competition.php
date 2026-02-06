<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'competition')]
#[ORM\UniqueConstraint(name: 'uq_competition_name_category_id', columns: ['name', 'category_id'])]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
])]
class Competition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    /**
     * Inverse side du ManyToMany Fixture<->Competition
     */
    #[ORM\ManyToMany(
        targetEntity: Fixture::class,
        mappedBy: 'competitions',
        fetch: 'EXTRA_LAZY'
    )]
    private Collection $fixtures;

    /**
     * 1 Competition -> N Editions (owning side = Edition via ManyToOne)
     * Ici on est sur le côté "inverse" (OneToMany) mais utile pour navigation.
     */
    #[ORM\OneToMany(
        mappedBy: 'competition',
        targetEntity: Edition::class,
        fetch: 'EXTRA_LAZY'
    )]
    private Collection $editions;

    public function __construct()
    {
        $this->fixtures = new ArrayCollection();
        $this->editions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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
            // IMPORTANT : ne pas appeler $fixture->addCompetition($this) ici,
            // sinon boucle. La synchro est faite côté Fixture.
        }
        return $this;
    }

    public function removeFixture(Fixture $fixture): self
    {
        $this->fixtures->removeElement($fixture);
        return $this;
    }

    /** @return Collection<int, Edition> */
    public function getEditions(): Collection
    {
        return $this->editions;
    }

    public function addEdition(Edition $edition): self
    {
        if (!$this->editions->contains($edition)) {
            $this->editions->add($edition);
            $edition->setCompetition($this);
        }
        return $this;
    }

    public function removeEdition(Edition $edition): self
    {
        $this->editions->removeElement($edition);
        return $this;
    }
}
