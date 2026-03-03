<?php

namespace App\Entity;

use App\Repository\PersonPhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonPhotoRepository::class)]
#[ORM\Table(name: 'person_photo')]
class PersonPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\Column(name: 'image_url', length: 150)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'caption', length: 150, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function __toString(): string
    {
        return (string) ($this->caption ?? $this->imageUrl ?? ('Photo #' . $this->id));
    }
}
