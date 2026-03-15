<?php

namespace App\Repository;

use App\Api\FixtureCollectionFormatter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class FixtureCollectionRepository
{
    private const ALGERIA_TEAM_ID = 1;
    private const DEFAULT_ITEMS_PER_PAGE = 20;
    private const MAX_ITEMS_PER_PAGE = 2000;

    public function __construct(
        private readonly Connection $connection,
        private readonly FixtureCollectionFormatter $formatter,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function findLegacyCollection(array $query): array
    {
        $criteria = $this->buildCriteria($query);
        $fixtureIds = $this->fetchFixtureIds($criteria);

        if ($fixtureIds === []) {
            return [];
        }

        return $this->fetchFormattedItems($fixtureIds);
    }

    /**
     * @param array<string, mixed> $query
     * @return array{
     *   items: list<array<string, mixed>>,
     *   meta: array{page:int, itemsPerPage:int, total:int, totalPages:int},
     *   summary?: array<string, int>
     * }
     */
    public function findSeniorMatchesPage(array $query, bool $includeSummary): array
    {
        $criteria = $this->buildCriteria($query);
        $total = $this->countFixtures($criteria);
        $fixtureIds = $total > 0 ? $this->fetchFixtureIds($criteria) : [];

        $response = [
            'items' => $fixtureIds === [] ? [] : $this->fetchFormattedItems($fixtureIds),
            'meta' => [
                'page' => $criteria['page'],
                'itemsPerPage' => $criteria['itemsPerPage'],
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $criteria['itemsPerPage'])),
            ],
        ];

        if ($includeSummary) {
            $response['summary'] = $this->fetchSummary($criteria);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function countFixtures(array $criteria): int
    {
        [$whereSql, $params] = $this->buildWhereClause($criteria);

        $total = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM fixture f {$whereSql}",
            $params
        );

        return (int) $total;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return list<int>
     */
    private function fetchFixtureIds(array $criteria): array
    {
        [$whereSql, $params] = $this->buildWhereClause($criteria);
        $params['limit'] = $criteria['itemsPerPage'];
        $params['offset'] = ($criteria['page'] - 1) * $criteria['itemsPerPage'];

        $ids = $this->connection->fetchFirstColumn(
            sprintf(
                'SELECT f.id
                FROM fixture f
                %s
                ORDER BY f.match_date %s, f.id %s
                LIMIT :limit OFFSET :offset',
                $whereSql,
                $criteria['matchDateOrder'],
                $criteria['matchDateOrder'],
            ),
            $params,
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        return array_values(array_map('intval', $ids));
    }

    /**
     * @param list<int> $fixtureIds
     * @return list<array<string, mixed>>
     */
    private function fetchFormattedItems(array $fixtureIds): array
    {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    SELECT
                        f.id,
                        f.external_match_no AS externalMatchNo,
                        f.match_date AS matchDate,
                        f.played,
                        f.is_official AS isOfficial,
                        f.notes,
                        season.name AS seasonName,
                        city.name AS cityName,
                        stadium.name AS stadiumName,
                        hostCountry.name AS countryStadiumName,
                        teamA.id AS teamAId,
                        COALESCE(teamA.display_name, nationalTeamA.name, clubA.name) AS teamAName,
                        COALESCE(countryA.iso2, clubCountryA.iso2) AS teamAIso2,
                        participantA.score AS scoreA,
                        categoryA.id AS categoryAId,
                        categoryA.name AS categoryAName,
                        teamB.id AS teamBId,
                        COALESCE(teamB.display_name, nationalTeamB.name, clubB.name) AS teamBName,
                        COALESCE(countryB.iso2, clubCountryB.iso2) AS teamBIso2,
                        participantB.score AS scoreB,
                        categoryB.id AS categoryBId,
                        categoryB.name AS categoryBName
                    FROM fixture f
                    LEFT JOIN season season ON season.id = f.season_id
                    LEFT JOIN city city ON city.id = f.city_id
                    LEFT JOIN stadium stadium ON stadium.id = f.stadium_id
                    LEFT JOIN country hostCountry ON hostCountry.id = f.country_id
                    LEFT JOIN fixture_participant participantA ON participantA.fixture_id = f.id AND participantA.role = 'A'
                    LEFT JOIN team teamA ON teamA.id = participantA.team_id
                    LEFT JOIN national_team nationalTeamA ON nationalTeamA.id = teamA.national_team_id
                    LEFT JOIN country countryA ON countryA.id = nationalTeamA.country_id
                    LEFT JOIN category categoryA ON categoryA.id = nationalTeamA.category_id
                    LEFT JOIN club clubA ON clubA.id = teamA.club_id
                    LEFT JOIN country clubCountryA ON clubCountryA.id = clubA.country_id
                    LEFT JOIN fixture_participant participantB ON participantB.fixture_id = f.id AND participantB.role = 'B'
                    LEFT JOIN team teamB ON teamB.id = participantB.team_id
                    LEFT JOIN national_team nationalTeamB ON nationalTeamB.id = teamB.national_team_id
                    LEFT JOIN country countryB ON countryB.id = nationalTeamB.country_id
                    LEFT JOIN category categoryB ON categoryB.id = nationalTeamB.category_id
                    LEFT JOIN club clubB ON clubB.id = teamB.club_id
                    LEFT JOIN country clubCountryB ON clubCountryB.id = clubB.country_id
                    WHERE f.id IN (%s)
                SQL,
                implode(', ', array_fill(0, count($fixtureIds), '?'))
            ),
            $fixtureIds,
            array_fill(0, count($fixtureIds), ParameterType::INTEGER)
        );

        $competitionRows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    SELECT
                        fc.fixture_id AS fixtureId,
                        competition.id,
                        competition.name
                    FROM fixture_competition fc
                    INNER JOIN competition competition ON competition.id = fc.competition_id
                    WHERE fc.fixture_id IN (%s)
                    ORDER BY competition.name ASC, competition.id ASC
                SQL,
                implode(', ', array_fill(0, count($fixtureIds), '?'))
            ),
            $fixtureIds,
            array_fill(0, count($fixtureIds), ParameterType::INTEGER)
        );

        $stageRows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    SELECT
                        fs.fixture_id AS fixtureId,
                        stage.id AS stageId,
                        stage.name AS stageName,
                        edition.id AS editionId,
                        edition.name AS editionName,
                        competition.id AS competitionId,
                        competition.name AS competitionName
                    FROM fixture_stage fs
                    INNER JOIN stage stage ON stage.id = fs.stage_id
                    LEFT JOIN edition edition ON edition.id = stage.edition_id
                    LEFT JOIN competition competition ON competition.id = edition.competition_id
                    WHERE fs.fixture_id IN (%s)
                    ORDER BY stage.sort_order ASC, stage.name ASC, stage.id ASC
                SQL,
                implode(', ', array_fill(0, count($fixtureIds), '?'))
            ),
            $fixtureIds,
            array_fill(0, count($fixtureIds), ParameterType::INTEGER)
        );

        $rowsById = [];

        foreach ($rows as $row) {
            $rowsById[(int) ($row['id'] ?? 0)] = $row;
        }

        $orderedRows = [];

        foreach ($fixtureIds as $fixtureId) {
            if (isset($rowsById[$fixtureId])) {
                $orderedRows[] = $rowsById[$fixtureId];
            }
        }

        return $this->formatter->formatItems($orderedRows, $competitionRows, $stageRows);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, int>
     */
    private function fetchSummary(array $criteria): array
    {
        [$whereSql, $params] = $this->buildWhereClause($criteria);

        $row = $this->connection->fetchAssociative(
            sprintf(
                <<<'SQL'
                SELECT
                    COUNT(*) AS totalMatches,
                    SUM(CASE WHEN matchStats.algeriaScore IS NOT NULL AND matchStats.opponentScore IS NOT NULL AND matchStats.algeriaScore > matchStats.opponentScore THEN 1 ELSE 0 END) AS wins,
                    SUM(CASE WHEN matchStats.algeriaScore IS NOT NULL AND matchStats.opponentScore IS NOT NULL AND matchStats.algeriaScore = matchStats.opponentScore THEN 1 ELSE 0 END) AS draws,
                    SUM(CASE WHEN matchStats.algeriaScore IS NOT NULL AND matchStats.opponentScore IS NOT NULL AND matchStats.algeriaScore < matchStats.opponentScore THEN 1 ELSE 0 END) AS losses,
                    COALESCE(SUM(CASE WHEN matchStats.algeriaScore IS NOT NULL AND matchStats.opponentScore IS NOT NULL THEN matchStats.algeriaScore ELSE 0 END), 0) AS goalsFor,
                    COALESCE(SUM(CASE WHEN matchStats.algeriaScore IS NOT NULL AND matchStats.opponentScore IS NOT NULL THEN matchStats.opponentScore ELSE 0 END), 0) AS goalsAgainst,
                    SUM(CASE WHEN matchStats.algeriaScore IS NOT NULL AND matchStats.opponentScore IS NOT NULL AND matchStats.opponentScore = 0 THEN 1 ELSE 0 END) AS cleanSheets,
                    COUNT(DISTINCT CASE WHEN matchStats.opponentName IS NOT NULL AND TRIM(matchStats.opponentName) <> '' THEN LOWER(TRIM(matchStats.opponentName)) END) AS uniqueOpponents,
                    COUNT(DISTINCT CASE WHEN matchStats.hostCountryName IS NOT NULL AND TRIM(matchStats.hostCountryName) <> '' THEN LOWER(TRIM(matchStats.hostCountryName)) END) AS uniqueHostCountries,
                    SUM(CASE WHEN matchStats.isOfficial = 1 THEN 1 ELSE 0 END) AS officialMatches
                FROM (
                    SELECT
                        f.id,
                        f.is_official AS isOfficial,
                        hostCountry.name AS hostCountryName,
                        algeriaParticipant.score AS algeriaScore,
                        MAX(CASE WHEN opponentParticipant.team_id <> :algeriaTeamId THEN opponentParticipant.score END) AS opponentScore,
                        MAX(
                            CASE WHEN opponentParticipant.team_id <> :algeriaTeamId
                                THEN COALESCE(opponentTeam.display_name, opponentNationalTeam.name, opponentClub.name)
                            END
                        ) AS opponentName
                    FROM fixture f
                    LEFT JOIN country hostCountry ON hostCountry.id = f.country_id
                    LEFT JOIN fixture_participant algeriaParticipant ON algeriaParticipant.fixture_id = f.id AND algeriaParticipant.team_id = :algeriaTeamId
                    LEFT JOIN fixture_participant opponentParticipant ON opponentParticipant.fixture_id = f.id
                    LEFT JOIN team opponentTeam ON opponentTeam.id = opponentParticipant.team_id
                    LEFT JOIN national_team opponentNationalTeam ON opponentNationalTeam.id = opponentTeam.national_team_id
                    LEFT JOIN club opponentClub ON opponentClub.id = opponentTeam.club_id
                    %s
                    GROUP BY f.id, f.is_official, hostCountry.name, algeriaParticipant.score
                ) matchStats
                SQL,
                $whereSql
            ),
            array_merge($params, ['algeriaTeamId' => self::ALGERIA_TEAM_ID])
        );

        return $this->formatter->formatSummary($row ?: null);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function buildCriteria(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $requestedItemsPerPage = (int) ($query['itemsPerPage'] ?? self::DEFAULT_ITEMS_PER_PAGE);
        $itemsPerPage = max(1, min($requestedItemsPerPage > 0 ? $requestedItemsPerPage : self::DEFAULT_ITEMS_PER_PAGE, self::MAX_ITEMS_PER_PAGE));
        $order = $query['order']['matchDate'] ?? $query['order[matchDate]'] ?? null;

        return [
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'matchDateOrder' => is_string($order) && strtoupper($order) === 'ASC' ? 'ASC' : 'DESC',
            'seasonName' => $this->normalizeString($this->getQueryValue($query, [
                'season.name',
                'season_name',
            ])),
            'competitionName' => $this->normalizeString($this->getQueryValue($query, [
                'competitions.name',
                'competitions_name',
            ])),
            'competitionId' => $this->normalizePositiveInt($this->getQueryValue($query, [
                'competitions.id',
                'competitions_id',
            ])),
            'teamNames' => $this->normalizeUniqueStrings([
                $this->getQueryValue($query, [
                    'participants.team.displayName',
                    'participants_team_displayName',
                ]),
                $this->getQueryValue($query, [
                    'participants.team.nationalTeam.name',
                    'participants_team_nationalTeam_name',
                ]),
                $this->getQueryValue($query, [
                    'participants.team.club.name',
                    'participants_team_club_name',
                ]),
            ]),
            'teamIso3s' => $this->normalizeUniqueIso3([
                $this->getQueryValue($query, [
                    'participants.team.country.iso3',
                    'participants_team_country_iso3',
                ]),
                $this->getQueryValue($query, [
                    'participants.team.nationalTeam.country.iso3',
                    'participants_team_nationalTeam_country_iso3',
                ]),
                $this->getQueryValue($query, [
                    'participants.team.club.country.iso3',
                    'participants_team_club_country_iso3',
                ]),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array{0:string, 1:array<string, mixed>}
     */
    private function buildWhereClause(array $criteria): array
    {
        $conditions = [];
        $params = [];

        if ($criteria['seasonName'] !== null) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM season seasonFilter
                WHERE seasonFilter.id = f.season_id
                  AND seasonFilter.name = :seasonName
            )';
            $params['seasonName'] = $criteria['seasonName'];
        }

        if ($criteria['competitionName'] !== null) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM fixture_competition fcFilter
                INNER JOIN competition competitionFilter ON competitionFilter.id = fcFilter.competition_id
                WHERE fcFilter.fixture_id = f.id
                  AND LOWER(competitionFilter.name) LIKE :competitionName
            )';
            $params['competitionName'] = '%' . mb_strtolower($criteria['competitionName']) . '%';
        }

        if ($criteria['competitionId'] !== null) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM fixture_competition fcFilterById
                WHERE fcFilterById.fixture_id = f.id
                  AND fcFilterById.competition_id = :competitionId
            )';
            $params['competitionId'] = $criteria['competitionId'];
        }

        if ($criteria['teamNames'] !== []) {
            $teamNameConditions = [];

            foreach ($criteria['teamNames'] as $index => $teamName) {
                $parameterName = 'teamName' . $index;
                $teamNameConditions[] = sprintf(
                    '(LOWER(COALESCE(teamFilter.display_name, \'\')) LIKE :%1$s OR LOWER(COALESCE(nationalTeamFilter.name, \'\')) LIKE :%1$s OR LOWER(COALESCE(clubFilter.name, \'\')) LIKE :%1$s)',
                    $parameterName
                );
                $params[$parameterName] = '%' . mb_strtolower($teamName) . '%';
            }

            $conditions[] = 'EXISTS (
                SELECT 1
                FROM fixture_participant participantFilter
                INNER JOIN team teamFilter ON teamFilter.id = participantFilter.team_id
                LEFT JOIN national_team nationalTeamFilter ON nationalTeamFilter.id = teamFilter.national_team_id
                LEFT JOIN club clubFilter ON clubFilter.id = teamFilter.club_id
                WHERE participantFilter.fixture_id = f.id
                  AND (' . implode(' OR ', $teamNameConditions) . ')
            )';
        }

        if ($criteria['teamIso3s'] !== []) {
            $teamIso3Conditions = [];

            foreach ($criteria['teamIso3s'] as $index => $teamIso3) {
                $parameterName = 'teamIso3' . $index;
                $teamIso3Conditions[] = sprintf(
                    '(UPPER(COALESCE(nationalCountryFilter.iso3, \'\')) = :%1$s OR UPPER(COALESCE(clubCountryFilter.iso3, \'\')) = :%1$s)',
                    $parameterName
                );
                $params[$parameterName] = $teamIso3;
            }

            $conditions[] = 'EXISTS (
                SELECT 1
                FROM fixture_participant participantCountryFilter
                INNER JOIN team teamCountryFilter ON teamCountryFilter.id = participantCountryFilter.team_id
                LEFT JOIN national_team nationalTeamCountryFilter ON nationalTeamCountryFilter.id = teamCountryFilter.national_team_id
                LEFT JOIN country nationalCountryFilter ON nationalCountryFilter.id = nationalTeamCountryFilter.country_id
                LEFT JOIN club clubCountryTeamFilter ON clubCountryTeamFilter.id = teamCountryFilter.club_id
                LEFT JOIN country clubCountryFilter ON clubCountryFilter.id = clubCountryTeamFilter.country_id
                WHERE participantCountryFilter.fixture_id = f.id
                  AND (' . implode(' OR ', $teamIso3Conditions) . ')
            )';
        }

        if ($conditions === []) {
            return ['', $params];
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    /**
     * @param list<mixed> $values
     * @return list<string>
     */
    private function normalizeUniqueStrings(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $stringValue = $this->normalizeString($value);

            if ($stringValue === null) {
                continue;
            }

            $normalized[] = $stringValue;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<mixed> $values
     * @return list<string>
     */
    private function normalizeUniqueIso3(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $stringValue = $this->normalizeString($value);

            if ($stringValue === null) {
                continue;
            }

            $normalized[] = strtoupper($stringValue);
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string> $keys
     */
    private function getQueryValue(array $query, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $query)) {
                return $query[$key];
            }
        }

        return null;
    }
}
