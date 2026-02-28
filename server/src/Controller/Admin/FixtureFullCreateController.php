<?php

namespace App\Controller\Admin;

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
use App\Entity\ScoresheetSubstitution;
use App\Form\Admin\Data\FixtureCompleteData;
use App\Form\Admin\Data\MatchPersonData;
use App\Form\Admin\FixtureCompleteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FixtureFullCreateController extends AbstractController
{
    #[Route('/admin/fixture/new-complete', name: 'admin_fixture_full_new')]
    public function __invoke(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = new FixtureCompleteData();
        $form = $this->createForm(FixtureCompleteType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fixture = (new Fixture())
                ->setExternalMatchNo($data->externalMatchNo)
                ->setSeason($data->season)
                ->setMatchday($data->matchday)
                ->setDivision($data->division)
                ->setCategory($data->category)
                ->setMatchDate($data->matchDate)
                ->setStadium($data->stadium)
                ->setCity($data->city)
                ->setCountry($data->country)
                ->setPlayed($data->played)
                ->setIsOfficial($data->isOfficial)
                ->setNotes($data->notes)
                ->setInternalNotes($data->internalNotes);

            foreach ($data->competitions as $competition) {
                $fixture->addCompetition($competition);
            }
            foreach ($data->editions as $edition) {
                $fixture->addEdition($edition);
            }
            foreach ($data->stages as $stage) {
                $fixture->addStage($stage);
            }

            $entityManager->persist($fixture);

            $entityManager->persist((new FixtureParticipant())
                ->setFixture($fixture)
                ->setTeam($data->teamA)
                ->setRole('A')
                ->setScore($data->scoreA));

            $entityManager->persist((new FixtureParticipant())
                ->setFixture($fixture)
                ->setTeam($data->teamB)
                ->setRole('B')
                ->setScore($data->scoreB));

            $scoresheet = (new Scoresheet())
                ->setFixture($fixture)
                ->setAttendance($data->attendance)
                ->setFixedTime($data->fixedTime)
                ->setKickoffTime($data->kickoffTime)
                ->setHalfTime($data->halfTime)
                ->setSecondHalfStart($data->secondHalfStart)
                ->setFullTime($data->fullTime)
                ->setStoppageTime($data->stoppageTime)
                ->setReservations($data->reservations)
                ->setSignedPlace($data->signedPlace)
                ->setSignedOn($data->signedOn)
                ->setStatus('1');
            $entityManager->persist($scoresheet);

            foreach ($data->lineups as $index => $lineupData) {
                if (!$lineupData->player && !$lineupData->playerName) {
                    continue;
                }

                $player = $this->resolvePlayer($entityManager, $lineupData->player, $lineupData->playerName, null);
                $team = $lineupData->teamRole === 'A' ? $data->teamA : $data->teamB;
                $entityManager->persist((new ScoresheetLineup())
                    ->setScoresheet($scoresheet)
                    ->setTeam($team)
                    ->setPlayer($player)
                    ->setPlayerNameText($lineupData->playerName)
                    ->setShirtNumber($lineupData->shirtNumber)
                    ->setLineupRole($lineupData->lineupRole)
                    ->setIsCaptain($lineupData->isCaptain)
                    ->setPosition($lineupData->position)
                    ->setSortOrder($index + 1));
            }

            foreach ($data->goals as $goalData) {
                if (!$goalData->scorer && !$goalData->scorerName) {
                    continue;
                }

                $player = $this->resolvePlayer($entityManager, $goalData->scorer, $goalData->scorerName, null);
                $team = $goalData->teamRole === 'A' ? $data->teamA : $data->teamB;
                $entityManager->persist((new MatchGoal())
                    ->setFixture($fixture)
                    ->setTeam($team)
                    ->setScorer($player)
                    ->setScorerText($goalData->scorerName)
                    ->setMinute($goalData->minute)
                    ->setGoalType($goalData->goalType));
            }

            foreach ($data->substitutions as $subData) {
                if ((!$subData->playerOut && !$subData->playerOutName) || (!$subData->playerIn && !$subData->playerInName)) {
                    continue;
                }

                $team = $subData->teamRole === 'A' ? $data->teamA : $data->teamB;
                $entityManager->persist((new ScoresheetSubstitution())
                    ->setScoresheet($scoresheet)
                    ->setTeam($team)
                    ->setPlayerOut($this->resolvePlayer($entityManager, $subData->playerOut, $subData->playerOutName, null))
                    ->setPlayerOutText($subData->playerOutName)
                    ->setPlayerIn($this->resolvePlayer($entityManager, $subData->playerIn, $subData->playerInName, null))
                    ->setPlayerInText($subData->playerInName)
                    ->setMinute($subData->minute));
            }

            $this->persistCoach($entityManager, $scoresheet, $data->teamA, $data->headCoachA, 'HEAD_COACH', 'Head');
            $this->persistCoach($entityManager, $scoresheet, $data->teamA, $data->assistantCoachA1, 'ASSISTANT_COACH', 'Assistant');
            $this->persistCoach($entityManager, $scoresheet, $data->teamA, $data->assistantCoachA2, 'ASSISTANT_COACH', 'Assistant');
            $this->persistCoach($entityManager, $scoresheet, $data->teamB, $data->headCoachB, 'HEAD_COACH', 'Head');
            $this->persistCoach($entityManager, $scoresheet, $data->teamB, $data->assistantCoachB1, 'ASSISTANT_COACH', 'Assistant');
            $this->persistCoach($entityManager, $scoresheet, $data->teamB, $data->assistantCoachB2, 'ASSISTANT_COACH', 'Assistant');

            $this->persistReferee($entityManager, $scoresheet, $data->mainReferee, 'MAIN_REFEREE');
            $this->persistReferee($entityManager, $scoresheet, $data->assistantReferee1, 'ASSISTANT_REFEREE');
            $this->persistReferee($entityManager, $scoresheet, $data->assistantReferee2, 'ASSISTANT_REFEREE');
            $this->persistReferee($entityManager, $scoresheet, $data->fourthReferee, 'FOURTH_OFFICIAL');

            $entityManager->flush();

            $this->addFlash('success', 'Match créé avec succès.');
            return $this->redirect('/admin?crudAction=index&crudControllerFqcn='.urlencode(FixtureCrudController::class));
        }

        return $this->render('admin/fixture/full_create.html.twig', [
            'form' => $form,
        ]);
    }

    private function resolvePlayer(EntityManagerInterface $entityManager, ?Player $player, ?string $name, ?\App\Entity\Country $nationality): ?Player
    {
        if ($player) {
            return $player;
        }

        if (!$name) {
            return null;
        }

        $personRepository = $entityManager->getRepository(Person::class);
        $person = $personRepository->findOneBy(['fullName' => $name]);
        if (!$person) {
            $person = (new Person())->setFullName($name)->setNationalityCountry($nationality);
            $entityManager->persist($person);
        }

        $playerRepository = $entityManager->getRepository(Player::class);
        $existingPlayer = $playerRepository->findOneBy(['person' => $person]);
        if ($existingPlayer) {
            return $existingPlayer;
        }

        $player = (new Player())->setPerson($person);
        $entityManager->persist($player);

        return $player;
    }

    private function resolvePerson(EntityManagerInterface $entityManager, MatchPersonData $data): ?Person
    {
        if ($data->person) {
            return $data->person;
        }

        if (!$data->name) {
            return null;
        }

        $person = $entityManager->getRepository(Person::class)->findOneBy(['fullName' => $data->name]);
        if ($person) {
            return $person;
        }

        $person = (new Person())
            ->setFullName($data->name)
            ->setNationalityCountry($data->nationality);
        $entityManager->persist($person);

        return $person;
    }

    private function persistCoach(EntityManagerInterface $entityManager, Scoresheet $scoresheet, $team, MatchPersonData $personData, string $scoresheetRole, string $coachRole): void
    {
        $person = $this->resolvePerson($entityManager, $personData);
        if (!$person && !$personData->name) {
            return;
        }

        if ($person) {
            $coach = $entityManager->getRepository(Coach::class)->findOneBy(['person' => $person, 'role' => $coachRole]);
            if (!$coach) {
                $coach = (new Coach())->setPerson($person)->setRole($coachRole);
                $entityManager->persist($coach);
            }
        }

        $entityManager->persist((new ScoresheetOfficial())
            ->setScoresheet($scoresheet)
            ->setRole($scoresheetRole)
            ->setPerson($person)
            ->setNameText($personData->name));
    }

    private function persistReferee(EntityManagerInterface $entityManager, Scoresheet $scoresheet, MatchPersonData $personData, string $role): void
    {
        $person = $this->resolvePerson($entityManager, $personData);
        if (!$person && !$personData->name) {
            return;
        }

        if ($person) {
            $referee = $entityManager->getRepository(Referee::class)->findOneBy(['person' => $person]);
            if (!$referee) {
                $referee = (new Referee())->setPerson($person);
                $entityManager->persist($referee);
            }
        }

        $entityManager->persist((new ScoresheetOfficial())
            ->setScoresheet($scoresheet)
            ->setRole($role)
            ->setPerson($person)
            ->setNameText($personData->name));
    }
}
