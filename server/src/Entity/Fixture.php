<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'fixture')]
#[ORM\Index(name: 'ix_fixture_match_date', columns: ['match_date'])]
#[ORM\Index(name: 'ix_fixture_competition_id', columns: ['competition_id'])]
#[ORM\Index(name: 'ix_fixture_season_id', columns: ['season_id'])]
#[ORM\Index(name: 'ix_fixture_edition_id', columns: ['edition_id'])]
#[ORM\Index(name: 'ix_fixture_stage_id', columns: ['stage_id'])]
class Fixture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'external_match_no', nullable: true)]
    private ?int $externalMatchNo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'competition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Competition $competition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Season $season = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Edition $edition = null;

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

    public function getCompetition(): ?Competition
    {
        return $this->competition;
    }

    public function setCompetition(?Competition $competition): static
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

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

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
