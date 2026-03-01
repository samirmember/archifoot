<?php

namespace App\Service\Admin;

use App\Entity\City;
use App\Entity\Coach;
use App\Entity\Fixture;
use App\Entity\FixtureParticipant;
use App\Entity\MatchGoal;
use App\Entity\Person;
use App\Entity\Player;
use App\Entity\Referee;
use App\Entity\Scoresheet;
use App\Entity\ScoresheetLineup;
use App\Entity\ScoresheetOfficial;
use App\Entity\ScoresheetStaff;
use App\Entity\ScoresheetSubstitution;
use App\Form\Admin\Model\FixtureFullInput;
use Doctrine\ORM\EntityManagerInterface;

class FixtureFullCreator
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function create(FixtureFullInput $input): Fixture
    {
        $fixture = (new Fixture())
            ->setSeason($input->season)
            ->setMatchDate($input->matchDate)
            ->setStadium($input->stadium)
            ->setCountry($input->country)
            ->setIsOfficial((bool) $input->isOfficial)
            ->setPlayed(true)
            ->setNotes($input->notes);

        $city = $this->findOrCreateCity($input->cityName, $input->country);
        if ($city !== null) {
            $fixture->setCity($city);
        }

        if ($input->competition !== null) {
            $fixture->addCompetition($input->competition);
        }

        if ($input->edition !== null) {
            $fixture->addEdition($input->edition);
        }

        if ($input->stage !== null) {
            $fixture->addStage($input->stage);
        }

        $this->em->persist($fixture);

        $participantA = (new FixtureParticipant())
            ->setFixture($fixture)
            ->setTeam($input->teamA)
            ->setRole('A')
            ->setScore($input->scoreA)
            ->setVenueRole('HOME');
        $participantB = (new FixtureParticipant())
            ->setFixture($fixture)
            ->setTeam($input->teamB)
            ->setRole('B')
            ->setScore($input->scoreB)
            ->setVenueRole('AWAY');

        $this->em->persist($participantA);
        $this->em->persist($participantB);

        $scoresheet = (new Scoresheet())
            ->setFixture($fixture)
            ->setAttendance($input->attendance);
        $this->em->persist($scoresheet);

        foreach ($input->lineups as $row) {
            if ($row->team === null || ($row->player === null && trim((string) $row->newPlayerName) === '')) {
                continue;
            }

            $player = $row->player ?? $this->findOrCreatePlayer($row->newPlayerName, $input->country);
            $this->em->persist(
                (new ScoresheetLineup())
                    ->setScoresheet($scoresheet)
                    ->setTeam($row->team)
                    ->setPlayer($player)
                    ->setPlayerNameText($player?->getPersonFullName())
                    ->setShirtNumber($row->shirtNumber)
                    ->setLineupRole($row->lineupRole)
                    ->setIsCaptain($row->captain)
                    ->setPosition($row->position)
                    ->setSortOrder($row->sortOrder)
            );
        }

        foreach ($input->goals as $row) {
            if ($row->team === null || ($row->scorer === null && trim((string) $row->newScorerName) === '')) {
                continue;
            }

            $scorer = $row->scorer ?? $this->findOrCreatePlayer($row->newScorerName, $input->country);
            $this->em->persist(
                (new MatchGoal())
                    ->setFixture($fixture)
                    ->setTeam($row->team)
                    ->setScorer($scorer)
                    ->setScorerText($scorer?->getPersonFullName())
                    ->setMinute($row->minute)
                    ->setGoalType($row->goalType)
            );
        }

        foreach ($input->substitutions as $row) {
            if ($row->team === null) {
                continue;
            }

            $out = $row->playerOut ?? $this->findOrCreatePlayer($row->newPlayerOutName, $input->country, false);
            $in = $row->playerIn ?? $this->findOrCreatePlayer($row->newPlayerInName, $input->country, false);
            if ($out === null || $in === null) {
                continue;
            }

            $this->em->persist(
                (new ScoresheetSubstitution())
                    ->setScoresheet($scoresheet)
                    ->setTeam($row->team)
                    ->setPlayerOut($out)
                    ->setPlayerIn($in)
                    ->setPlayerOutText($out->getPersonFullName())
                    ->setPlayerInText($in->getPersonFullName())
                    ->setMinute($row->minute)
            );
        }

        foreach ($input->officials as $row) {
            $person = $row->person ?? $this->findOrCreatePerson($row->newFullName, $row->nationality, false);
            if ($person === null || trim((string) $row->role) === '') {
                continue;
            }

            $referee = $this->em->getRepository(Referee::class)->findOneBy(['person' => $person]) ?? (new Referee())->setPerson($person);
            $this->em->persist($referee);

            $this->em->persist(
                (new ScoresheetOfficial())
                    ->setScoresheet($scoresheet)
                    ->setRole($row->role)
                    ->setPerson($person)
                    ->setNameText($person->getFullName())
            );
        }

        foreach ($input->staff as $row) {
            $person = $row->person ?? $this->findOrCreatePerson($row->newFullName, $row->nationality, false);
            if ($person === null || $row->team === null || trim((string) $row->role) === '') {
                continue;
            }

            $coach = $this->em->getRepository(Coach::class)->findOneBy(['person' => $person]) ?? (new Coach())->setPerson($person);
            $coach->setRole($row->role);
            $this->em->persist($coach);

            $this->em->persist(
                (new ScoresheetStaff())
                    ->setScoresheet($scoresheet)
                    ->setTeam($row->team)
                    ->setPerson($person)
                    ->setRole($row->role)
            );
        }

        $this->em->flush();

        return $fixture;
    }

    private function findOrCreatePlayer(?string $fullName, $nationality, bool $throwOnEmpty = true): ?Player
    {
        $name = trim((string) $fullName);
        if ($name === '') {
            if ($throwOnEmpty) {
                throw new \InvalidArgumentException('Nom de joueur requis.');
            }

            return null;
        }

        $player = $this->em->createQueryBuilder()
            ->select('pl')
            ->from(Player::class, 'pl')
            ->join('pl.person', 'p')
            ->where('LOWER(p.fullName) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($player instanceof Player) {
            return $player;
        }

        $person = $this->findOrCreatePerson($name, $nationality);
        $player = (new Player())->setPerson($person);
        $this->em->persist($player);

        return $player;
    }

    private function findOrCreateCity(?string $name, $country): ?City
    {
        $cityName = trim((string) $name);
        if ($cityName === '') {
            return null;
        }

        $city = $this->em->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->where('LOWER(c.name) = :name')
            ->setParameter('name', mb_strtolower($cityName))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($city instanceof City) {
            return $city;
        }

        $city = (new City())
            ->setName($cityName)
            ->setCountry($country);
        $this->em->persist($city);

        return $city;
    }

    private function findOrCreatePerson(?string $fullName, $nationality, bool $throwOnEmpty = true): ?Person
    {
        $name = trim((string) $fullName);
        if ($name === '') {
            if ($throwOnEmpty) {
                throw new \InvalidArgumentException('Nom de personne requis.');
            }

            return null;
        }

        $person = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->where('LOWER(p.fullName) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($person instanceof Person) {
            return $person;
        }

        $person = (new Person())
            ->setFullName($name)
            ->setNationalityCountry($nationality);
        $this->em->persist($person);

        return $person;
    }
}
