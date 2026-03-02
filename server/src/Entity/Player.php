<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\Table(name: 'player')]
class Player
{
    private ?string $newBirthCityName = null;
    private ?string $newBirthRegionName = null;
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

    public function getPhotoUrl(): ?string
    {
        return $this->person?->getPhotoUrl();
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

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->ensurePersonExists();
        $this->person?->setPhotoUrl($photoUrl);

        return $this;
    }

    public function getFeaturePhotoUrl(): ?string
    {
        return $this->person?->getFeaturePhotoUrl();
    }

    public function setFeaturePhotoUrl(?string $featurePhotoUrl): static
    {
        $this->ensurePersonExists();
        $this->person?->setFeaturePhotoUrl($featurePhotoUrl);

        return $this;
    }


    private function ensurePersonExists(): void
    {
        if ($this->person === null) {
            $this->person = new Person();
        }
    }
}
