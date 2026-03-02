<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'person')]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'full_name', length: 200, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(name: 'birth_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'birth_city_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?City $birthCity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'birth_region_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Region $birthRegion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'birth_country_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Country $birthCountry = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'nationality_country_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Country $nationalityCountry = null;

    #[ORM\Column(name: 'photo_url', length: 150, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'feature_photo_url', length: 150, nullable: true)]
    private ?string $featurePhotoUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBirthCity(): ?City
    {
        return $this->birthCity;
    }

    public function setBirthCity(?City $birthCity): static
    {
        $this->birthCity = $birthCity;

        return $this;
    }

    public function getBirthRegion(): ?Region
    {
        return $this->birthRegion;
    }

    public function setBirthRegion(?Region $birthRegion): static
    {
        $this->birthRegion = $birthRegion;

        return $this;
    }

    public function getBirthCountry(): ?Country
    {
        return $this->birthCountry;
    }

    public function setBirthCountry(?Country $birthCountry): static
    {
        $this->birthCountry = $birthCountry;

        return $this;
    }

    public function getNationalityCountry(): ?Country
    {
        return $this->nationalityCountry;
    }

    public function setNationalityCountry(?Country $nationalityCountry): static
    {
        $this->nationalityCountry = $nationalityCountry;

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

    public function getFeaturePhotoUrl(): ?string
    {
        return $this->featurePhotoUrl;
    }

    public function setFeaturePhotoUrl(?string $featurePhotoUrl): static
    {
        $this->featurePhotoUrl = $featurePhotoUrl;

        return $this;
    }
}
