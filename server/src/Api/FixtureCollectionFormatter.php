<?php

namespace App\Api;

final class FixtureCollectionFormatter
{
    /**
     * @param list<array<string, mixed>> $fixtureRows
     * @param list<array<string, mixed>> $competitionRows
     * @param list<array<string, mixed>> $stageRows
     * @return list<array<string, mixed>>
     */
    public function formatItems(array $fixtureRows, array $competitionRows, array $stageRows): array
    {
        $competitionsByFixture = $this->indexCompetitionsByFixture($competitionRows);
        $stagesByFixture = $this->indexStagesByFixture($stageRows);

        $items = [];

        foreach ($fixtureRows as $row) {
            $fixtureId = (int) ($row['id'] ?? 0);

            $items[] = [
                'externalMatchNo' => isset($row['externalMatchNo']) ? (int) $row['externalMatchNo'] : null,
                'competitions' => $competitionsByFixture[$fixtureId] ?? [],
                'matchDate' => $this->formatMatchDate($row['matchDate'] ?? null),
                'played' => $this->toNullableBool($row['played'] ?? null),
                'isOfficial' => $this->toNullableBool($row['isOfficial'] ?? null),
                'notes' => $row['notes'] ?? null,
                'seasonName' => $row['seasonName'] ?? null,
                'stages' => $stagesByFixture[$fixtureId] ?? [],
                'teamA' => $this->buildTeam(
                    $row['teamAId'] ?? null,
                    $row['teamAName'] ?? null,
                    $row['teamAIso2'] ?? null,
                ),
                'teamB' => $this->buildTeam(
                    $row['teamBId'] ?? null,
                    $row['teamBName'] ?? null,
                    $row['teamBIso2'] ?? null,
                ),
                'scoreA' => isset($row['scoreA']) ? (int) $row['scoreA'] : null,
                'scoreB' => isset($row['scoreB']) ? (int) $row['scoreB'] : null,
                'countryStadiumName' => $row['countryStadiumName'] ?? null,
                'cityName' => $row['cityName'] ?? null,
                'stadiumName' => $row['stadiumName'] ?? null,
                'categories' => [
                    [
                        'id' => isset($row['categoryAId']) ? (int) $row['categoryAId'] : null,
                        'name' => $row['categoryAName'] ?? null,
                    ],
                    [
                        'id' => isset($row['categoryBId']) ? (int) $row['categoryBId'] : null,
                        'name' => $row['categoryBName'] ?? null,
                    ],
                ],
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed>|null $summaryRow
     * @return array<string, int>
     */
    public function formatSummary(?array $summaryRow): array
    {
        $totalMatches = (int) ($summaryRow['totalMatches'] ?? 0);
        $wins = (int) ($summaryRow['wins'] ?? 0);
        $draws = (int) ($summaryRow['draws'] ?? 0);
        $losses = (int) ($summaryRow['losses'] ?? 0);
        $goalsFor = (int) ($summaryRow['goalsFor'] ?? 0);
        $goalsAgainst = (int) ($summaryRow['goalsAgainst'] ?? 0);
        $officialMatches = (int) ($summaryRow['officialMatches'] ?? 0);

        return [
            'totalMatches' => $totalMatches,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'winRate' => $this->toPercentage($wins, $totalMatches),
            'goalsFor' => $goalsFor,
            'goalsAgainst' => $goalsAgainst,
            'goalDifference' => $goalsFor - $goalsAgainst,
            'cleanSheets' => (int) ($summaryRow['cleanSheets'] ?? 0),
            'uniqueOpponents' => (int) ($summaryRow['uniqueOpponents'] ?? 0),
            'uniqueHostCountries' => (int) ($summaryRow['uniqueHostCountries'] ?? 0),
            'officialMatches' => $officialMatches,
            'officialRate' => $this->toPercentage($officialMatches, $totalMatches),
        ];
    }

    /**
     * @param list<array<string, mixed>> $competitionRows
     * @return array<int, list<array{id:int|null, name:string|null}>>
     */
    private function indexCompetitionsByFixture(array $competitionRows): array
    {
        $indexed = [];

        foreach ($competitionRows as $row) {
            $fixtureId = (int) ($row['fixtureId'] ?? 0);
            $indexed[$fixtureId] ??= [];
            $indexed[$fixtureId][] = [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'name' => $row['name'] ?? null,
            ];
        }

        return $indexed;
    }

    /**
     * @param list<array<string, mixed>> $stageRows
     * @return array<int, list<array<string, mixed>>>
     */
    private function indexStagesByFixture(array $stageRows): array
    {
        $indexed = [];

        foreach ($stageRows as $row) {
            $fixtureId = (int) ($row['fixtureId'] ?? 0);
            $indexed[$fixtureId] ??= [];
            $indexed[$fixtureId][] = [
                'id' => isset($row['stageId']) ? (int) $row['stageId'] : null,
                'name' => $row['stageName'] ?? null,
                'edition' => $this->buildEdition($row),
            ];
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function buildEdition(array $row): ?array
    {
        $editionId = $row['editionId'] ?? null;
        $editionName = $row['editionName'] ?? null;
        $competitionId = $row['competitionId'] ?? null;
        $competitionName = $row['competitionName'] ?? null;

        if ($editionId === null && $editionName === null) {
            return null;
        }

        return [
            'id' => isset($editionId) ? (int) $editionId : null,
            'name' => $editionName,
            'competition' => $competitionId === null && $competitionName === null
                ? null
                : [
                    'id' => isset($competitionId) ? (int) $competitionId : null,
                    'name' => $competitionName,
                ],
        ];
    }

    /**
     * @return array{id:int|null, name:string|null, iso2:string|null}|null
     */
    private function buildTeam(mixed $id, mixed $name, mixed $iso2): ?array
    {
        if ($id === null && $name === null && $iso2 === null) {
            return null;
        }

        return [
            'id' => isset($id) ? (int) $id : null,
            'name' => is_string($name) ? $name : null,
            'iso2' => is_string($iso2) ? $iso2 : null,
        ];
    }

    private function formatMatchDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(DATE_ATOM);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toNullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);
    }

    private function toPercentage(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }
}
