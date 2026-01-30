<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'matchday')]
#[ORM\UniqueConstraint(name: 'uq_matchday_edition_id_label', columns: ['edition_id', 'label'])]
class Matchday
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Edition $edition = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?int $number = null;

    #[ORM\Column(name: 'start_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
}
