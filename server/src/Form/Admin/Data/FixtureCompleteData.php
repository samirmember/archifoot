<?php

namespace App\Form\Admin\Data;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Division;
use App\Entity\Edition;
use App\Entity\Matchday;
use App\Entity\Season;
use App\Entity\Stadium;
use App\Entity\Stage;
use App\Entity\Team;

class FixtureCompleteData
{
    public ?int $externalMatchNo = null;
    public ?Season $season = null;
    /** @var Competition[] */
    public array $competitions = [];
    /** @var Edition[] */
    public array $editions = [];
    /** @var Stage[] */
    public array $stages = [];
    public ?Matchday $matchday = null;
    public ?Division $division = null;
    public ?Category $category = null;
    public ?\DateTimeInterface $matchDate = null;
    public ?Stadium $stadium = null;
    public ?City $city = null;
    public ?Country $country = null;
    public ?bool $played = true;
    public ?bool $isOfficial = true;
    public ?string $notes = null;
    public ?string $internalNotes = null;

    public ?Team $teamA = null;
    public ?Team $teamB = null;
    public ?int $scoreA = null;
    public ?int $scoreB = null;

    public ?int $attendance = null;
    public ?string $fixedTime = null;
    public ?string $kickoffTime = null;
    public ?string $halfTime = null;
    public ?string $secondHalfStart = null;
    public ?string $fullTime = null;
    public ?string $stoppageTime = null;
    public ?string $reservations = null;
    public ?string $signedPlace = null;
    public ?\DateTimeInterface $signedOn = null;

    /** @var MatchLineupData[] */
    public array $lineups = [];
    /** @var MatchGoalData[] */
    public array $goals = [];
    /** @var MatchSubstitutionData[] */
    public array $substitutions = [];

    public MatchPersonData $headCoachA;
    public MatchPersonData $assistantCoachA1;
    public MatchPersonData $assistantCoachA2;
    public MatchPersonData $headCoachB;
    public MatchPersonData $assistantCoachB1;
    public MatchPersonData $assistantCoachB2;

    public MatchPersonData $mainReferee;
    public MatchPersonData $assistantReferee1;
    public MatchPersonData $assistantReferee2;
    public MatchPersonData $fourthReferee;

    public function __construct()
    {
        $this->lineups[] = new MatchLineupData();
        $this->goals[] = new MatchGoalData();
        $this->substitutions[] = new MatchSubstitutionData();

        $this->headCoachA = new MatchPersonData();
        $this->assistantCoachA1 = new MatchPersonData();
        $this->assistantCoachA2 = new MatchPersonData();
        $this->headCoachB = new MatchPersonData();
        $this->assistantCoachB1 = new MatchPersonData();
        $this->assistantCoachB2 = new MatchPersonData();

        $this->mainReferee = new MatchPersonData();
        $this->assistantReferee1 = new MatchPersonData();
        $this->assistantReferee2 = new MatchPersonData();
        $this->fourthReferee = new MatchPersonData();
    }
}
