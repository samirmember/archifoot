<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class FixtureController extends AbstractController
{
    private const ALGERIA_TEAM_ID = 1;

    #[Route('/api/senior-national-team/matchs/{externalMatchNo}/scoresheet', name: 'api_senior_match_scoresheet_show', methods: ['GET'])]
    public function scoresheet(int $externalMatchNo, Connection $connection): JsonResponse
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
                WHERE f.external_match_no = :external_match_no
            SQL;
        $fixture = $connection->fetchAssociative(
            $sql,
            ['external_match_no' => $externalMatchNo]
        );

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

        $competitions = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    c.id,
                    c.name
                FROM fixture_competition fc
                INNER JOIN competition c ON c.id = fc.competition_id
                WHERE fc.fixture_id = :fixtureId
                ORDER BY c.name ASC, c.id ASC
            SQL,
            ['fixtureId' => $fixtureId]
        );

        $stages = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    s.id AS stage_id,
                    s.name AS stage_name,
                    e.id AS edition_id,
                    e.name AS edition_name,
                    c.id AS competition_id,
                    c.name AS competition_name
                FROM fixture_stage fs
                INNER JOIN stage s ON s.id = fs.stage_id
                LEFT JOIN edition e ON e.id = s.edition_id
                LEFT JOIN competition c ON c.id = e.competition_id
                WHERE fs.fixture_id = :fixtureId
                ORDER BY s.sort_order ASC, s.name ASC, s.id ASC
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

        $competitions = array_map(
            static fn (array $competition): array => [
                'id' => isset($competition['id']) ? (int) $competition['id'] : null,
                'name' => $competition['name'] ?? null,
            ],
            $competitions
        );

        $stages = array_map(
            static fn (array $stage): array => [
                'id' => isset($stage['stage_id']) ? (int) $stage['stage_id'] : null,
                'name' => $stage['stage_name'] ?? null,
                'edition' => $stage['edition_id'] === null && $stage['edition_name'] === null
                    ? null
                    : [
                        'id' => isset($stage['edition_id']) ? (int) $stage['edition_id'] : null,
                        'name' => $stage['edition_name'] ?? null,
                        'competition' => $stage['competition_id'] === null && $stage['competition_name'] === null
                            ? null
                            : [
                                'id' => isset($stage['competition_id']) ? (int) $stage['competition_id'] : null,
                                'name' => $stage['competition_name'] ?? null,
                            ],
                    ],
            ],
            $stages
        );

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
                    sc.status AS "status",
                    (
                        SELECT ps.full_name
                        FROM scoresheet_staff ssf
                        INNER JOIN person ps ON ps.id = ssf.person_id
                        WHERE ssf.scoresheet_id = sc.id
                          AND ssf.role = 'HEAD_COACH'
                        ORDER BY ssf.id ASC
                        LIMIT 1
                    ) AS "coachName"
                FROM scoresheet sc
                WHERE sc.fixture_id = :fixtureId
            SQL,
            ['fixtureId' => $fixtureId]
        );

        $scoresheetId = $scoresheet['id'] ?? null;

        $lineups = [];
        $substitutions = [];
        $officials = [];
        $staffs = [];

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
                    ORDER BY ss.minute ASC
                SQL,
                ['scoresheetId' => $scoresheetId]
            );

            $officials = $connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        so.id,
                        r.label AS "role",
                        so.name_text AS "nameText",
                        p.full_name AS "personName",
                        c.name AS "nationality"
                    FROM scoresheet_official so
                    LEFT JOIN person p ON p.id = so.person_id
                    LEFT JOIN country c ON c.id = p.nationality_country_id
                    LEFT JOIN role r ON r.code = so.role
                    WHERE so.scoresheet_id = :scoresheetId
                    ORDER BY so.id ASC
                SQL,
                ['scoresheetId' => $scoresheetId]
            );

            $staffs = $connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        ssf.id,
                        ssf.role AS "roleCode",
                        r.label AS "role",
                        p.full_name AS "personName",
                        t.display_name AS "teamName",
                        co.iso2 AS "teamIso2",
                        c.name AS "nationality"
                    FROM scoresheet_staff ssf
                    LEFT JOIN role r ON r.code = ssf.role
                    LEFT JOIN person p ON p.id = ssf.person_id
                    LEFT JOIN team t ON t.id = ssf.team_id
                    LEFT JOIN national_team nt ON nt.id = t.national_team_id
                    LEFT JOIN country co ON co.id = nt.country_id
                    LEFT JOIN country c ON c.id = p.nationality_country_id
                    WHERE ssf.scoresheet_id = :scoresheetId
                    ORDER BY ssf.team_id ASC, ssf.id ASC
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
                ORDER BY mg.minute ASC
            SQL,
            ['fixtureId' => $fixtureId]
        );

        return $this->json([
            'fixture' => [
                ...$fixture,
                'competitions' => $competitions,
                'stages' => $stages,
                'teamA' => $participantA,
                'teamB' => $participantB,
            ],
            'scoresheet' => $scoresheet ?: null,
            'lineups' => $lineups,
            'substitutions' => $substitutions,
            'officials' => $officials,
            'staffs' => $staffs,
            'goals' => $goals,
        ]);
    }

    #[Route('/api/senior-national-team/matchs/totals', name: 'api_fixtures_totals', methods: ['GET'])]
    public function totals(Connection $connection): JsonResponse
    {
        $totals = $connection->fetchAssociative(
            <<<'SQL'
                WITH algeria_matches AS (
                    SELECT
                        f.id,
                        fp.score AS algeria_score,
                        MAX(CASE WHEN fp_opponent.team_id <> :algeriaTeamId THEN fp_opponent.score END) AS opponent_score
                    FROM fixture f
                    INNER JOIN fixture_participant fp ON fp.fixture_id = f.id AND fp.team_id = :algeriaTeamId
                    LEFT JOIN fixture_participant fp_opponent ON fp_opponent.fixture_id = f.id
                    WHERE f.played = true
                    GROUP BY f.id, fp.score
                )
                SELECT
                    COUNT(*) AS total_matches,
                    SUM(CASE WHEN algeria_score IS NOT NULL AND opponent_score IS NOT NULL AND algeria_score > opponent_score THEN 1 ELSE 0 END) AS total_wins,
                    COALESCE(SUM(algeria_score), 0) AS total_goals,
                    (
                        SELECT COUNT(*)
                        FROM trophy_award ta
                        WHERE ta.team_id = :algeriaTeamId
                          AND ta.rank = 1
                    ) AS trophy_wins
                FROM algeria_matches
            SQL,
            ['algeriaTeamId' => self::ALGERIA_TEAM_ID]
        );

        return $this->json([
            'totalMatches' => (int) ($totals['total_matches'] ?? 0),
            'totalWins' => (int) ($totals['total_wins'] ?? 0),
            'totalGoals' => (int) ($totals['total_goals'] ?? 0),
            'trophyWins' => (int) ($totals['trophy_wins'] ?? 0),
        ]);
    }
}
