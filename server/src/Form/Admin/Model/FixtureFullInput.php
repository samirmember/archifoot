<?php

namespace App\Form\Admin\Model;

use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Edition;
use App\Entity\Season;
use App\Entity\Stage;
use App\Entity\Stadium;
use App\Entity\Team;

class FixtureFullInput
{
    public ?Season $season = null;
    public ?Competition $competition = null;
    public ?Edition $edition = null;
    public ?Stage $stage = null;
    public ?Team $teamA = null;
    public ?Team $teamB = null;
    public ?int $scoreA = null;
    public ?int $scoreB = null;
    public ?\DateTimeInterface $matchDate = null;
    public ?Stadium $stadium = null;
    public ?Country $country = null;
    public ?string $cityName = null;
    public ?bool $isOfficial = true;
    public ?string $notes = null;
    public ?int $attendance = null;

    /** @var list<LineupInput> */
    public array $lineups = [];

    /** @var list<GoalInput> */
    public array $goals = [];

    /** @var list<SubstitutionInput> */
    public array $substitutions = [];

    /** @var list<OfficialInput> */
    public array $officials = [];

    /** @var list<StaffInput> */
    public array $staff = [];
}
