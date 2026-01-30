<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_batch')]
class ImportBatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'source_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SourceFile $sourceFile = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entity = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $inserted = null;

    #[ORM\Column(nullable: true)]
    private ?int $updated = null;

    #[ORM\Column(nullable: true)]
    private ?int $skipped = null;

    #[ORM\Column(name: 'started_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $finishedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceFile(): ?SourceFile
    {
        return $this->sourceFile;
    }

    public function setSourceFile(?SourceFile $sourceFile): static
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(?string $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getInserted(): ?int
    {
        return $this->inserted;
    }

    public function setInserted(?int $inserted): static
    {
        $this->inserted = $inserted;

        return $this;
    }

    public function getUpdated(): ?int
    {
        return $this->updated;
    }

    public function setUpdated(?int $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getSkipped(): ?int
    {
        return $this->skipped;
    }

    public function setSkipped(?int $skipped): static
    {
        $this->skipped = $skipped;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }
}
