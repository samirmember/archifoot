<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stage')]
class Stage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Edition $edition = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'stage_type', length: 30, nullable: true)]
    private ?string $stageType = null;

    #[ORM\Column(name: 'is_final', nullable: true)]
    private ?bool $isFinal = null;

    #[ORM\Column(name: 'sort_order', nullable: true)]
    private ?int $sortOrder = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStageType(): ?string
    {
        return $this->stageType;
    }

    public function setStageType(?string $stageType): static
    {
        $this->stageType = $stageType;

        return $this;
    }

    public function isFinal(): ?bool
    {
        return $this->isFinal;
    }

    public function setIsFinal(?bool $isFinal): static
    {
        $this->isFinal = $isFinal;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
