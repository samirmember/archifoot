<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'source_file')]
class SourceFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(name: 'checksum_sha1', length: 40, nullable: true)]
    private ?string $checksumSha1 = null;

    #[ORM\Column(name: 'imported_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $importedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getChecksumSha1(): ?string
    {
        return $this->checksumSha1;
    }

    public function setChecksumSha1(?string $checksumSha1): static
    {
        $this->checksumSha1 = $checksumSha1;

        return $this;
    }

    public function getImportedAt(): ?\DateTimeInterface
    {
        return $this->importedAt;
    }

    public function setImportedAt(?\DateTimeInterface $importedAt): static
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
