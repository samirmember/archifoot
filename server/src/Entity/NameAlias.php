<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'name_alias')]
#[ORM\UniqueConstraint(name: 'uq_name_alias_entity_type_entity_id_normalized', columns: ['entity_type', 'entity_id', 'normalized'])]
class NameAlias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'entity_type', length: 30, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(name: 'entity_id', nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $alias = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $normalized = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getNormalized(): ?string
    {
        return $this->normalized;
    }

    public function setNormalized(?string $normalized): static
    {
        $this->normalized = $normalized;

        return $this;
    }
}
