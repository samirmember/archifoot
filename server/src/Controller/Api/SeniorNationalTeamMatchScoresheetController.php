<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SeniorNationalTeamMatchScoresheetController extends AbstractController
{
    #[Route('/api/senior-national-team/matchs/{externalMatchNo}/scoresheet', name: 'api_senior_match_scoresheet_show', methods: ['GET'])]
    public function __invoke(int $externalMatchNo, Connection $connection): JsonResponse
    {
        $sql = <<<'SQL'
                SELECT
                    f.id,
                    f.external_match_no AS "externalMatchNo",
                    f.match_date AS "matchDate",
                    f.played,
                    f.is_official AS "isOfficial",
                    f.notes,
                    s.name AS "seasonName",
                    city.name AS "cityName",
                    st.name AS "stadiumName",
                    c.name AS "countryStadiumName"
                FROM fixture f
                LEFT JOIN season s ON s.id = f.season_id
                LEFT JOIN city city ON city.id = f.city_id
                LEFT JOIN stadium st ON st.id = f.stadium_id
                LEFT JOIN country c ON c.id = f.country_id
                WHERE f.id = :external_match_no
            SQL;
            // dd($sql);
        $fixture = $connection->fetchAssociative(
            $sql,
            ['external_match_no' => $externalMatchNo]
        );

        // dd($fixture);

        if ($fixture === false) {
            return $this->json(['message' => 'Match introuvable.'], 404);
        }

        $fixtureId = $fixture['id'];

        $participants = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    fp.role,
                    fp.score,
                    t.id AS "teamId",
                    t.display_name AS "teamName",
                    co.iso2 AS "teamIso2",
                    cat.name AS "categoryName"
                FROM fixture_participant fp
                LEFT JOIN team t ON t.id = fp.team_id
                LEFT JOIN national_team nt ON nt.id = t.national_team_id
                LEFT JOIN country co ON co.id = nt.country_id
                LEFT JOIN category cat ON cat.id = nt.category_id
                WHERE fp.fixture_id = :fixtureId
            SQL,
            ['fixtureId' => $fixtureId]
        );

        $participantA = null;
        $participantB = null;

        foreach ($participants as $participant) {
            if (($participant['role'] ?? null) === 'A') {
                $participantA = $participant;
            }
            if (($participant['role'] ?? null) === 'B') {
                $participantB = $participant;
            }
        }

        $scoresheet = $connection->fetchAssociative(
            <<<'SQL'
                SELECT
                    sc.id,
                    sc.attendance,
                    sc.fixed_time AS "fixedTime",
                    sc.kickoff_time AS "kickoffTime",
                    sc.half_time AS "halfTime",
                    sc.second_half_start AS "secondHalfStart",
                    sc.full_time AS "fullTime",
                    sc.stoppage_time AS "stoppageTime",
                    sc.match_stop_time AS "matchStopTime",
                    sc.reservations,
                    sc.report,
                    sc.signed_place AS "signedPlace",
                    sc.signed_on AS "signedOn",
                    sc.form_state AS "formState",
                    p.full_name AS "coachName"
                FROM scoresheet sc
                LEFT JOIN person p ON p.id = sc.coach_id
                WHERE sc.fixture_id = :fixtureId
            SQL,
            ['fixtureId' => $fixtureId]
        );

        $scoresheetId = $scoresheet['id'] ?? null;

        $lineups = [];
        $substitutions = [];
        $officials = [];

        if ($scoresheetId !== null) {
            $lineups = $connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        sl.id,
                        sl.lineup_role AS "lineupRole",
                        sl.shirt_number AS "shirtNumber",
                        sl.sort_order AS "sortOrder",
                        sl.is_captain AS "isCaptain",
                        sl.player_name_text AS "playerNameText",
                        t.display_name AS "teamName",
                        pos.label AS "positionName",
                        person.full_name AS "playerName"
                    FROM scoresheet_lineup sl
                    LEFT JOIN team t ON t.id = sl.team_id
                    LEFT JOIN player pl ON pl.id = sl.player_id
                    LEFT JOIN person person ON person.id = pl.person_id
                    LEFT JOIN position pos ON pos.id = sl.position_id
                    WHERE sl.scoresheet_id = :scoresheetId
                    ORDER BY sl.lineup_role ASC, sl.sort_order ASC, sl.id ASC
                SQL,
                ['scoresheetId' => $scoresheetId]
            );

            $substitutions = $connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        ss.id,
                        ss.minute,
                        ss.player_out_text AS "playerOutText",
                        ss.player_in_text AS "playerInText",
                        t.display_name AS "teamName",
                        p_out.full_name AS "playerOutName",
                        p_in.full_name AS "playerInName"
                    FROM scoresheet_substitution ss
                    LEFT JOIN team t ON t.id = ss.team_id
                    LEFT JOIN player pl_out ON pl_out.id = ss.player_out_id
                    LEFT JOIN person p_out ON p_out.id = pl_out.person_id
                    LEFT JOIN player pl_in ON pl_in.id = ss.player_in_id
                    LEFT JOIN person p_in ON p_in.id = pl_in.person_id
                    WHERE ss.scoresheet_id = :scoresheetId
                    ORDER BY ss.id ASC
                SQL,
                ['scoresheetId' => $scoresheetId]
            );

            $officials = $connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        so.id,
                        so.role,
                        so.name_text AS "nameText",
                        p.full_name AS "personName"
                    FROM scoresheet_official so
                    LEFT JOIN person p ON p.id = so.person_id
                    WHERE so.scoresheet_id = :scoresheetId
                    ORDER BY so.id ASC
                SQL,
                ['scoresheetId' => $scoresheetId]
            );
        }

        $goals = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    mg.id,
                    mg.minute,
                    mg.goal_type AS "goalType",
                    mg.scorer_text AS "scorerText",
                    t.display_name AS "teamName",
                    p.full_name AS "scorerName"
                FROM match_goal mg
                LEFT JOIN team t ON t.id = mg.team_id
                LEFT JOIN player pl ON pl.id = mg.scorer_id
                LEFT JOIN person p ON p.id = pl.person_id
                WHERE mg.fixture_id = :fixtureId
                ORDER BY mg.id ASC
            SQL,
            ['fixtureId' => $fixtureId]
        );

        return $this->json([
            'fixture' => [
                ...$fixture,
                'teamA' => $participantA,
                'teamB' => $participantB,
            ],
            'scoresheet' => $scoresheet ?: null,
            'lineups' => $lineups,
            'substitutions' => $substitutions,
            'officials' => $officials,
            'goals' => $goals,
        ]);
    }
}
