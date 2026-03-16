<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'coach')]
class Coach
{
    private ?string $newBirthCityName = null;
    private ?string $newBirthRegionName = null;

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


    #[ORM\Column(name: 'external_number', length: 50, nullable: true)]
    private ?string $externalNumber = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'death_city_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?City $deathCity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'death_region_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Region $deathRegion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'death_country_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Country $deathCountry = null;

    #[ORM\Column(nullable: true)]
    private ?string $career = null;

    /** @var array<int, string>|null */
    #[ORM\Column(name: 'main_clubs', type: 'json', nullable: true)]
    private ?array $mainClubs = null;

    #[ORM\Column(name: 'algeria_player_caps', nullable: true)]
    private ?int $algeriaPlayerCaps = null;

    #[ORM\Column(name: 'foreign_player_caps', nullable: true)]
    private ?int $foreignPlayerCaps = null;

    #[ORM\Column(name: 'head_matches', nullable: true)]
    private ?int $headMatches = null;

    #[ORM\Column(name: 'assistant_matches', nullable: true)]
    private ?int $assistantMatches = null;

    #[ORM\Column(nullable: true)]
    private ?int $callups = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;


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


    public function getExternalNumber(): ?string
    {
        return $this->externalNumber;
    }

    public function setExternalNumber(?string $externalNumber): static
    {
        $this->externalNumber = $externalNumber;

        return $this;
    }

    public function getDeathCity(): ?City
    {
        return $this->deathCity;
    }

    public function setDeathCity(?City $deathCity): static
    {
        $this->deathCity = $deathCity;

        return $this;
    }

    public function getDeathRegion(): ?Region
    {
        return $this->deathRegion;
    }

    public function setDeathRegion(?Region $deathRegion): static
    {
        $this->deathRegion = $deathRegion;

        return $this;
    }

    public function getDeathCountry(): ?Country
    {
        return $this->deathCountry;
    }

    public function setDeathCountry(?Country $deathCountry): static
    {
        $this->deathCountry = $deathCountry;

        return $this;
    }

    public function getCareer(): ?string
    {
        return $this->career;
    }

    public function setCareer(?string $career): static
    {
        $this->career = $career;

        return $this;
    }

    /** @return array<int, string>|null */
    public function getMainClubs(): ?array
    {
        return $this->mainClubs;
    }

    /** @param array<int, string>|null $mainClubs */
    public function setMainClubs(?array $mainClubs): static
    {
        $this->mainClubs = $mainClubs;

        return $this;
    }

    public function getAlgeriaPlayerCaps(): ?int
    {
        return $this->algeriaPlayerCaps;
    }

    public function setAlgeriaPlayerCaps(?int $algeriaPlayerCaps): static
    {
        $this->algeriaPlayerCaps = $algeriaPlayerCaps;

        return $this;
    }

    public function getForeignPlayerCaps(): ?int
    {
        return $this->foreignPlayerCaps;
    }

    public function setForeignPlayerCaps(?int $foreignPlayerCaps): static
    {
        $this->foreignPlayerCaps = $foreignPlayerCaps;

        return $this;
    }

    public function getHeadMatches(): ?int
    {
        return $this->headMatches;
    }

    public function setHeadMatches(?int $headMatches): static
    {
        $this->headMatches = $headMatches;

        return $this;
    }

    public function getAssistantMatches(): ?int
    {
        return $this->assistantMatches;
    }

    public function setAssistantMatches(?int $assistantMatches): static
    {
        $this->assistantMatches = $assistantMatches;

        return $this;
    }

    public function getCallups(): ?int
    {
        return $this->callups;
    }

    public function setCallups(?int $callups): static
    {
        $this->callups = $callups;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->person?->getPhotoUrl();
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->ensurePersonExists();
        $this->person?->setPhotoUrl($photoUrl);

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

    public function getPersonBirthDate(): ?\DateTimeInterface
    {
        return $this->person?->getBirthDate();
    }

    public function setPersonBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->ensurePersonExists();
        $this->person?->setBirthDate($birthDate);

        return $this;
    }

    public function getPersonBirthCity(): ?City
    {
        return $this->person?->getBirthCity();
    }

    public function setPersonBirthCity(?City $birthCity): static
    {
        $this->ensurePersonExists();
        $this->person?->setBirthCity($birthCity);

        return $this;
    }

    public function getPersonBirthRegion(): ?Region
    {
        return $this->person?->getBirthRegion();
    }

    public function setPersonBirthRegion(?Region $birthRegion): static
    {
        $this->ensurePersonExists();
        $this->person?->setBirthRegion($birthRegion);

        return $this;
    }

    public function getPersonBirthCountry(): ?Country
    {
        return $this->person?->getBirthCountry();
    }

    public function setPersonBirthCountry(?Country $birthCountry): static
    {
        $this->ensurePersonExists();
        $this->person?->setBirthCountry($birthCountry);

        return $this;
    }

    public function getPersonNationalityCountry(): ?Country
    {
        return $this->person?->getNationalityCountry();
    }

    public function setPersonNationalityCountry(?Country $nationalityCountry): static
    {
        $this->ensurePersonExists();
        $this->person?->setNationalityCountry($nationalityCountry);

        return $this;
    }

    public function getPersonNationalityCountryName(): ?string
    {
        return $this->person?->getNationalityCountry()?->getName();
    }

    public function getNewBirthCityName(): ?string
    {
        return $this->newBirthCityName;
    }

    public function setNewBirthCityName(?string $newBirthCityName): static
    {
        $this->newBirthCityName = $newBirthCityName;

        return $this;
    }

    public function getNewBirthRegionName(): ?string
    {
        return $this->newBirthRegionName;
    }

    public function setNewBirthRegionName(?string $newBirthRegionName): static
    {
        $this->newBirthRegionName = $newBirthRegionName;

        return $this;
    }

    private function ensurePersonExists(): void
    {
        if ($this->person === null) {
            $this->person = new Person();
        }
    }
}
