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
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'fixture')]
#[ORM\Index(name: 'ix_fixture_match_date', columns: ['match_date'])]
#[ORM\Index(name: 'ix_fixture_competition_id', columns: ['competition_id'])]
#[ORM\Index(name: 'ix_fixture_season_id', columns: ['season_id'])]
#[ORM\Index(name: 'ix_fixture_edition_id', columns: ['edition_id'])]
#[ApiResource(
    formats: ['json' => ['application/json']],
    normalizationContext: ['groups' => ['fixture:read']],
    operations: [new Get(), new GetCollection()],
)]
class Fixture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'external_match_no', nullable: true)]
    #[Groups(['fixture:read'])]
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

    #[ORM\ManyToMany(
        targetEntity: Stage::class,
        inversedBy: 'fixtures',
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\JoinTable(name: 'fixture_stage')]
    #[ORM\JoinColumn(name: 'fixture_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'stage_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $stages;

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
    #[Groups(['fixture:read'])]
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
    #[Groups(['fixture:read'])]
    private ?bool $played = null;

    #[ORM\Column(name: 'is_official', nullable: true)]
    #[Groups(['fixture:read'])]
    private ?bool $isOfficial = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['fixture:read'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->competitions = new ArrayCollection();
        $this->editions = new ArrayCollection();
        $this->stages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['fixture:read'])]
    public function getSeasonName(): ?string
    {
        return $this->season?->getName();
    }

    /** @return string[] */
    #[Groups(['fixture:read'])]
    public function getCompetitionNames(): array
    {
        return $this->competitions
            ->map(static fn(Competition $competition): ?string => $competition->getName())
            ->filter(static fn(?string $name): bool => $name !== null && $name !== '')
            ->getValues()
            ->toArray();
    }

    /** @return string[] */
    #[Groups(['fixture:read'])]
    public function getEditionNames(): array
    {
        return $this->editions
            ->map(static fn(Edition $edition): ?string => $edition->getName())
            ->filter(static fn(?string $name): bool => $name !== null && $name !== '')
            ->getValues()
            ->toArray();
    }

    /** @return string[] */
    #[Groups(['fixture:read'])]
    public function getStageNames(): array
    {
        return $this->stages
            ->map(static fn(Stage $stage): ?string => $stage->getName())
            ->filter(static fn(?string $name): bool => $name !== null && $name !== '')
            ->getValues()
            ->toArray();
    }

    #[Groups(['fixture:read'])]
    public function getCountryName(): ?string
    {
        return $this->country?->getName();
    }

    #[Groups(['fixture:read'])]
    public function getCityName(): ?string
    {
        return $this->city?->getName();
    }

    #[Groups(['fixture:read'])]
    public function getStadiumName(): ?string
    {
        return $this->stadium?->getName();
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

    /** @return Collection<int, Stage> */
    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function addStage(Stage $stage): self
    {
        if (!$this->stages->contains($stage)) {
            $this->stages->add($stage);
            $stage->addFixture($this); // synchro inverse
        }
        return $this;
    }

    public function removeStage(Stage $stage): self
    {
        if ($this->stages->removeElement($stage)) {
            $stage->removeFixture($this);
        }
        return $this;
    }

    /**
     * Utiliser ceci pour ajouter le stage, édition et compétition en une seule opération
     * (puisque Stage -> Edition -> Competition).
     */
    public function addStageWithConsistency(Stage $stage): self
    {
        $this->addStage($stage);

        $edition = $stage->getEdition();
        if ($edition !== null) {
            $this->addEdition($edition);

            $competition = $edition->getCompetition();
            if ($competition !== null) {
                $this->addCompetition($competition);
            }
        }

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
