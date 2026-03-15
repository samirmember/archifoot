<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Api/FixtureCollectionFormatter.php';

use App\Api\FixtureCollectionFormatter;

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed for {$label}.\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$formatter = new FixtureCollectionFormatter();

$items = $formatter->formatItems(
    [[
        'id' => 10,
        'externalMatchNo' => 455,
        'matchDate' => '2004-06-05',
        'played' => 1,
        'isOfficial' => 0,
        'notes' => 'Match test',
        'seasonName' => '2004',
        'cityName' => 'Alger',
        'stadiumName' => '5 Juillet',
        'countryStadiumName' => 'Algérie',
        'teamAId' => 1,
        'teamAName' => 'Algérie',
        'teamAIso2' => 'DZ',
        'scoreA' => 2,
        'categoryAId' => 1,
        'categoryAName' => 'Sénior',
        'teamBId' => 2,
        'teamBName' => 'France',
        'teamBIso2' => 'FR',
        'scoreB' => 1,
        'categoryBId' => 1,
        'categoryBName' => 'Sénior',
    ]],
    [[
        'fixtureId' => 10,
        'id' => 3,
        'name' => 'CAN',
    ]],
    [[
        'fixtureId' => 10,
        'stageId' => 7,
        'stageName' => 'Quart de finale',
        'editionId' => 9,
        'editionName' => '2004 en Tunisie',
        'competitionId' => 3,
        'competitionName' => 'CAN',
    ]]
);

assertSameValue(1, count($items), 'items count');
assertSameValue(455, $items[0]['externalMatchNo'], 'externalMatchNo');
assertSameValue('2004-06-05T00:00:00+00:00', $items[0]['matchDate'], 'matchDate');
assertSameValue(false, $items[0]['isOfficial'], 'isOfficial');
assertSameValue('CAN', $items[0]['competitions'][0]['name'], 'competition name');
assertSameValue('Quart de finale', $items[0]['stages'][0]['name'], 'stage name');
assertSameValue('2004 en Tunisie', $items[0]['stages'][0]['edition']['name'], 'edition name');
assertSameValue('DZ', $items[0]['teamA']['iso2'], 'teamA iso2');
assertSameValue('Sénior', $items[0]['categories'][1]['name'], 'categoryB name');

$summary = $formatter->formatSummary([
    'totalMatches' => 12,
    'wins' => 7,
    'draws' => 3,
    'losses' => 2,
    'goalsFor' => 18,
    'goalsAgainst' => 9,
    'cleanSheets' => 5,
    'uniqueOpponents' => 11,
    'uniqueHostCountries' => 8,
    'officialMatches' => 4,
]);

assertSameValue(58, $summary['winRate'], 'winRate');
assertSameValue(9, $summary['goalDifference'], 'goalDifference');
assertSameValue(33, $summary['officialRate'], 'officialRate');

echo "Fixture collection formatter test: OK\n";
