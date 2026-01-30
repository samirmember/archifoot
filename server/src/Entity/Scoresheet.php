<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scoresheet')]
#[ORM\UniqueConstraint(name: 'uq_scoresheet_fixture_id', columns: ['fixture_id'])]
class Scoresheet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'fixture_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Fixture $fixture = null;

    #[ORM\Column(nullable: true)]
    private ?int $attendance = null;

    #[ORM\Column(name: 'fixed_time', length: 5, nullable: true)]
    private ?string $fixedTime = null;

    #[ORM\Column(name: 'kickoff_time', length: 5, nullable: true)]
    private ?string $kickoffTime = null;

    #[ORM\Column(name: 'half_time', length: 5, nullable: true)]
    private ?string $halfTime = null;

    #[ORM\Column(name: 'second_half_start', length: 5, nullable: true)]
    private ?string $secondHalfStart = null;

    #[ORM\Column(name: 'full_time', length: 5, nullable: true)]
    private ?string $fullTime = null;

    #[ORM\Column(name: 'stoppage_time', length: 5, nullable: true)]
    private ?string $stoppageTime = null;

    #[ORM\Column(name: 'match_stop_time', length: 5, nullable: true)]
    private ?string $matchStopTime = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reservations = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $report = null;

    #[ORM\Column(name: 'signed_place', length: 150, nullable: true)]
    private ?string $signedPlace = null;

    #[ORM\Column(name: 'signed_on', type: 'date', nullable: true)]
    private ?\DateTimeInterface $signedOn = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'coach_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Person $coach = null;

    #[ORM\Column(name: 'form_state', length: 1, nullable: true)]
    private ?string $formState = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFixture(): ?Fixture
    {
        return $this->fixture;
    }

    public function setFixture(?Fixture $fixture): static
    {
        $this->fixture = $fixture;

        return $this;
    }

    public function getAttendance(): ?int
    {
        return $this->attendance;
    }

    public function setAttendance(?int $attendance): static
    {
        $this->attendance = $attendance;

        return $this;
    }

    public function getFixedTime(): ?string
    {
        return $this->fixedTime;
    }

    public function setFixedTime(?string $fixedTime): static
    {
        $this->fixedTime = $fixedTime;

        return $this;
    }

    public function getKickoffTime(): ?string
    {
        return $this->kickoffTime;
    }

    public function setKickoffTime(?string $kickoffTime): static
    {
        $this->kickoffTime = $kickoffTime;

        return $this;
    }

    public function getHalfTime(): ?string
    {
        return $this->halfTime;
    }

    public function setHalfTime(?string $halfTime): static
    {
        $this->halfTime = $halfTime;

        return $this;
    }

    public function getSecondHalfStart(): ?string
    {
        return $this->secondHalfStart;
    }

    public function setSecondHalfStart(?string $secondHalfStart): static
    {
        $this->secondHalfStart = $secondHalfStart;

        return $this;
    }

    public function getFullTime(): ?string
    {
        return $this->fullTime;
    }

    public function setFullTime(?string $fullTime): static
    {
        $this->fullTime = $fullTime;

        return $this;
    }

    public function getStoppageTime(): ?string
    {
        return $this->stoppageTime;
    }

    public function setStoppageTime(?string $stoppageTime): static
    {
        $this->stoppageTime = $stoppageTime;

        return $this;
    }

    public function getMatchStopTime(): ?string
    {
        return $this->matchStopTime;
    }

    public function setMatchStopTime(?string $matchStopTime): static
    {
        $this->matchStopTime = $matchStopTime;

        return $this;
    }

    public function getReservations(): ?string
    {
        return $this->reservations;
    }

    public function setReservations(?string $reservations): static
    {
        $this->reservations = $reservations;

        return $this;
    }

    public function getReport(): ?string
    {
        return $this->report;
    }

    public function setReport(?string $report): static
    {
        $this->report = $report;

        return $this;
    }

    public function getSignedPlace(): ?string
    {
        return $this->signedPlace;
    }

    public function setSignedPlace(?string $signedPlace): static
    {
        $this->signedPlace = $signedPlace;

        return $this;
    }

    public function getSignedOn(): ?\DateTimeInterface
    {
        return $this->signedOn;
    }

    public function setSignedOn(?\DateTimeInterface $signedOn): static
    {
        $this->signedOn = $signedOn;

        return $this;
    }

    public function getCoach(): ?Person
    {
        return $this->coach;
    }

    public function setCoach(?Person $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    public function getFormState(): ?string
    {
        return $this->formState;
    }

    public function setFormState(?string $formState): static
    {
        $this->formState = $formState;

        return $this;
    }
}
