<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\Api\FixtureController;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TestFixtureController extends FixtureController
{
    public function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

final class FixtureScoresheetTestConnection extends Connection
{
    public function __construct()
    {
    }

    public function fetchAssociative(string $query, array $params = [], array $types = []): array|false
    {
        if (str_contains($query, 'FROM fixture f')) {
            return [
                'id' => 77,
                'externalMatchNo' => 455,
                'matchDate' => '2025-07-10',
                'played' => 1,
                'isOfficial' => 1,
                'notes' => 'Match test',
                'seasonName' => '2025',
                'cityName' => 'Tizi Ouzou',
                'stadiumName' => 'Hocine Ait Ahmed',
                'countryStadiumName' => 'Algérie',
            ];
        }

        if (str_contains($query, 'FROM scoresheet sc')) {
            return false;
        }

        return false;
    }

    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
        if (str_contains($query, 'FROM fixture_participant fp')) {
            return [
                [
                    'role' => 'A',
                    'score' => 2,
                    'outcome' => 1,
                    'teamId' => 1,
                    'teamName' => 'Algérie',
                    'teamIso2' => 'DZ',
                    'categoryName' => 'Sénior',
                ],
                [
                    'role' => 'B',
                    'score' => 1,
                    'outcome' => 0,
                    'teamId' => 2,
                    'teamName' => 'Ouganda',
                    'teamIso2' => 'UG',
                    'categoryName' => 'Sénior',
                ],
            ];
        }

        if (str_contains($query, 'FROM fixture_competition fc')) {
            return [
                ['id' => 3, 'name' => 'CAN'],
            ];
        }

        if (str_contains($query, 'FROM fixture_stage fs')) {
            return [
                [
                    'stage_id' => 7,
                    'stage_name' => 'Quart de finale',
                    'edition_id' => 9,
                    'edition_name' => '2025 au Maroc',
                    'competition_id' => 3,
                    'competition_name' => 'CAN',
                ],
            ];
        }

        if (str_contains($query, 'FROM match_goal mg')) {
            return [];
        }

        return [];
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed for {$label}.\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$controller = new TestFixtureController();
$connection = new FixtureScoresheetTestConnection();

$response = $controller->scoresheet(455, $connection);
$payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

assertSameValue(1, $payload['fixture']['teamA']['outcome'] ?? null, 'teamA outcome');
assertSameValue(0, $payload['fixture']['teamB']['outcome'] ?? null, 'teamB outcome');
assertSameValue(2, $payload['fixture']['teamA']['score'] ?? null, 'teamA score');
assertSameValue(1, $payload['fixture']['teamB']['score'] ?? null, 'teamB score');

echo "Fixture scoresheet response test: OK\n";
