<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity]
#[ORM\Table(name: 'fixture')]
#[ORM\Index(name: 'ix_fixture_match_date', columns: ['match_date'])]
#[ORM\Index(name: 'ix_fixture_competition_id', columns: ['competition_id'])]
#[ORM\Index(name: 'ix_fixture_season_id', columns: ['season_id'])]
#[ORM\Index(name: 'ix_fixture_edition_id', columns: ['edition_id'])]
#[ORM\Index(name: 'ix_fixture_stage_id', columns: ['stage_id'])]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
)]
class Fixture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'external_match_no', nullable: true)]
    private ?int $externalMatchNo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Season $season = null;

    /**
     * Owning side -> écrit la table fixture_competition
     */
    #[ORM\ManyToMany(
        targetEntity: Competition::class,
        inversedBy: 'fixtures',
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\JoinTable(name: 'fixture_competition')]
    #[ORM\JoinColumn(name: 'fixture_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'competition_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $competitions;

    /**
     * Owning side -> écrit la table fixture_edition
     */
    #[ORM\ManyToMany(
        targetEntity: Edition::class,
        inversedBy: 'fixtures',
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\JoinTable(name: 'fixture_edition')]
    #[ORM\JoinColumn(name: 'fixture_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'edition_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $editions;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'stage_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Stage $stage = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'matchday_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Matchday $matchday = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'division_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Division $division = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(name: 'match_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $matchDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'stadium_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Stadium $stadium = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'city_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?City $city = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Country $country = null;

    #[ORM\Column(nullable: true)]
    private ?bool $played = null;

    #[ORM\Column(name: 'is_official', nullable: true)]
    private ?bool $isOfficial = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->competitions = new ArrayCollection();
        $this->editions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalMatchNo(): ?int
    {
        return $this->externalMatchNo;
    }

    public function setExternalMatchNo(?int $externalMatchNo): static
    {
        $this->externalMatchNo = $externalMatchNo;

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

    /** @return Collection<int, Competition> */
    public function getCompetitions(): Collection
    {
        return $this->competitions;
    }

    public function addCompetition(Competition $competition): self
    {
        if (!$this->competitions->contains($competition)) {
            $this->competitions->add($competition);
            // synchro inverse side (objet)
            $competition->addFixture($this);
        }
        return $this;
    }

    public function removeCompetition(Competition $competition): self
    {
        if ($this->competitions->removeElement($competition)) {
            $competition->removeFixture($this);
        }
        return $this;
    }

    /** @return Collection<int, Edition> */
    public function getEditions(): Collection
    {
        return $this->editions;
    }

    /**
     * Case where we should add to fixture both: addEdition() and addCompetition()
     */
    // public function addEdition(Edition $edition): self
    // {
    //     if (!$this->editions->contains($edition)) {
    //         $this->editions->add($edition);
    //         $edition->addFixture($this);
    //     }
    //     return $this;
    // }

    /**
     * Case where we should use only : addEdition() so the edition and the competition will be added
     */
    public function addEdition(Edition $edition): self
    {
        if (!$this->editions->contains($edition)) {
            $this->editions->add($edition);
            $edition->addFixture($this);

            // cohérence métier : Edition => Competition
            $competition = $edition->getCompetition();
            if ($competition !== null) {
                $this->addCompetition($competition);
            }
        }
        return $this;
    }

    public function removeEdition(Edition $edition): self
    {
        if ($this->editions->removeElement($edition)) {
            $edition->removeFixture($this);
        }
        return $this;
    }

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function setStage(?Stage $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    public function getMatchday(): ?Matchday
    {
        return $this->matchday;
    }

    public function setMatchday(?Matchday $matchday): static
    {
        $this->matchday = $matchday;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getMatchDate(): ?\DateTimeInterface
    {
        return $this->matchDate;
    }

    public function setMatchDate(?\DateTimeInterface $matchDate): static
    {
        $this->matchDate = $matchDate;

        return $this;
    }

    public function getStadium(): ?Stadium
    {
        return $this->stadium;
    }

    public function setStadium(?Stadium $stadium): static
    {
        $this->stadium = $stadium;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function isPlayed(): ?bool
    {
        return $this->played;
    }

    public function setPlayed(?bool $played): static
    {
        $this->played = $played;

        return $this;
    }

    public function isOfficial(): ?bool
    {
        return $this->isOfficial;
    }

    public function setIsOfficial(?bool $isOfficial): static
    {
        $this->isOfficial = $isOfficial;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
