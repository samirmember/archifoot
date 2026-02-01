<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'country')]
#[ORM\UniqueConstraint(name: 'uq_country_fifa_code', columns: ['fifa_code'])]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
])]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $iso2 = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $iso3 = null;

    #[ORM\Column(name: 'fifa_code', length: 3, nullable: true)]
    private ?string $fifaCode = null;

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

    public function getIso2(): ?string
    {
        return $this->iso2;
    }

    public function setIso2(?string $iso2): static
    {
        $this->iso2 = $iso2;

        return $this;
    }

    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    public function setIso3(?string $iso3): static
    {
        $this->iso3 = $iso3;

        return $this;
    }

    public function getFifaCode(): ?string
    {
        return $this->fifaCode;
    }

    public function setFifaCode(?string $fifaCode): static
    {
        $this->fifaCode = $fifaCode;

        return $this;
    }
}
