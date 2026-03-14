<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import:player-infos-update',
    description: 'Importe les informations joueurs depuis players.xlsx et met a jour person/player.'
)]
class ImportPlayerInfosUpdateCommand extends Command
{
    /** @var array<string, int> */
    private array $countryByNormalizedName = [];

    /** @var array<string, int> */
    private array $positionByNormalizedLabelOrCode = [];

    /** @var array<string, int> */
    private array $regionByNormalizedName = [];

    /** @var array<string, int> */
    private array $cityByNormalizedName = [];

    /** @var array<string, array{playerId:int, personId:int, fullName:string}> */
    private array $playerByNormalizedName = [];

    private int $createdCountries = 0;
    private int $createdRegions = 0;
    private int $createdCities = 0;

    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Chemin du fichier xlsx', 'players.xlsx')
            ->addArgument('sheet', InputArgument::OPTIONAL, 'Nom de la feuille (optionnel)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getArgument('file');
        $sheetName = $input->getArgument('sheet');

        if (!is_file($file)) {
            $output->writeln(sprintf('<error>Fichier introuvable: %s</error>', $file));
            return Command::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = is_string($sheetName) && $sheetName !== ''
            ? $spreadsheet->getSheetByName($sheetName)
            : $this->resolveTargetSheet($spreadsheet);

        if ($sheet === null) {
            $output->writeln('<error>Aucune feuille compatible trouvee (headers attendus: num ext, joueur, selections, buts, ...).</error>');
            return Command::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (\count($rows) < 2) {
            $output->writeln('<error>Le fichier ne contient pas de donnees a importer.</error>');
            return Command::FAILURE;
        }

        $headers = array_map(static fn (mixed $value): string => trim((string) $value), $rows[1]);

        $this->warmupCountryCache();
        $this->warmupPositionCache();
        $this->warmupPlayerCache();

        $stats = [
            'rows' => 0,
            'updated' => 0,
            'missing' => 0,
            'ignored' => 0,
            'invalid_birth_date' => 0,
            'invalid_death_date' => 0,
            'unknown_position' => 0,
            'country_wilaya_detected' => 0,
            'algeria_wilaya_detected' => 0,
        ];

        /** @var array<int, string> $notFoundPlayers */
        $notFoundPlayers = [];

        $this->db->beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index === 1) {
                    continue;
                }

                $stats['rows']++;

                $playerName = $this->toNullableString($this->getByHeader($headers, $row, ['joueur']));
                if ($playerName === null) {
                    $stats['ignored']++;
                    continue;
                }

                $playerData = $this->playerByNormalizedName[$this->normalize($playerName)] ?? null;
                $externalNumber = $this->toNullableString($this->getByHeader($headers, $row, ['num ext', 'num']));
                if ($playerData === null) {
                    $stats['missing']++;
                    $notFoundPlayers[] = sprintf('Numéro %d: %s', $externalNumber, $playerName);
                    continue;
                }
                $selections = $this->toNullableInt($this->getByHeader($headers, $row, ['selections', 'sélections']));
                $goals = $this->toNullableInt($this->getByHeader($headers, $row, ['buts', 'buts ']));
                $birthPlace = $this->toNullableString($this->getByHeader($headers, $row, ['lieu de naissance', 'lieu de naissancee']));
                $wilaya = $this->toNullableString($this->getByHeader($headers, $row, ['wilaya']));
                $positionLabel = $this->toNullableString($this->getByHeader($headers, $row, ['poste']));
                $deathDateRaw = $this->toNullableString($this->getByHeader($headers, $row, ['date du dece', 'date de dece', 'date de deces', 'date du décé']));
                $mainClubsRaw = $this->toNullableString($this->getByHeader($headers, $row, ['principaux clubs']));
                $careerRaw = $this->toNullableString($this->getByHeader($headers, $row, ['années']));
                

                $birthDate = $this->parseSpreadsheetDate($this->getByHeader($headers, $row, ['date de naissance']));
                if ($birthDate === null && $this->toNullableString($this->getByHeader($headers, $row, ['date de naissance'])) !== null) {
                    $stats['invalid_birth_date']++;
                }

                $deathDate = $this->parseDeathDate($deathDateRaw);
                if ($deathDate === null && $deathDateRaw !== null) {
                    $stats['invalid_death_date']++;
                }

                $mainClubs = $this->parseMainClubs($mainClubsRaw);
                $career = $this->parseCareer($careerRaw);
                $positionId = $this->resolvePositionId($positionLabel);
                if ($positionLabel !== null && $positionId === null) {
                    $stats['unknown_position']++;
                }

                $personUpdates = [
                    'birth_date' => $birthDate,
                    'death_date' => $deathDate,
                ];

                if ($externalNumber !== null) {
                    $personUpdates['external_number'] = $externalNumber;
                    $personUpdates['photo_url'] = sprintf('%s.webp', $externalNumber);
                }

                $locationUpdates = $this->resolveLocationUpdates($wilaya, $birthPlace, $stats);
                $personUpdates = array_merge($personUpdates, $locationUpdates);

                $this->db->update('person', $personUpdates, ['id' => $playerData['personId']]);

                $playerUpdates = [
                    'selections' => $selections,
                    'goals' => $goals,
                    'main_clubs' => $mainClubs === null ? null : json_encode($mainClubs, JSON_UNESCAPED_UNICODE),
                    'career' => $career,
                ];

                if ($externalNumber !== null) {
                    $playerUpdates['external_number'] = $externalNumber;
                }
                if ($positionId !== null) {
                    $playerUpdates['primary_position_id'] = $positionId;
                }

                $this->db->update('player', $playerUpdates, ['id' => $playerData['playerId']]);

                $stats['updated']++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $output->writeln('<error>Erreur pendant l import: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Import terminé.</info>');
        $output->writeln(sprintf('Lignes lues: %d', $stats['rows']));
        $output->writeln(sprintf('Joueurs mis à jour: %d', $stats['updated']));
        $output->writeln(sprintf('Joueurs introuvables: %d', $stats['missing']));
        $output->writeln(sprintf('Lignes ignorées: %d', $stats['ignored']));
        $output->writeln(sprintf('Dates de naissance invalides: %d', $stats['invalid_birth_date']));
        $output->writeln(sprintf('Dates de décès invalides: %d', $stats['invalid_death_date']));
        $output->writeln(sprintf('Postes non résolus: %d', $stats['unknown_position']));
        $output->writeln(sprintf('Wilaya reconnue comme pays: %d', $stats['country_wilaya_detected']));
        $output->writeln(sprintf('Wilaya rattachée à l\'Algérie: %d', $stats['algeria_wilaya_detected']));
        $output->writeln(sprintf('Pays créés: %d', $this->createdCountries));
        $output->writeln(sprintf('Régions créées: %d', $this->createdRegions));
        $output->writeln(sprintf('Villes créées: %d', $this->createdCities));

        if ($notFoundPlayers !== []) {
            $output->writeln('');
            $output->writeln('<comment>Joueurs introuvables (ignorés):</comment>');
            foreach ($notFoundPlayers as $line) {
                $output->writeln(' - ' . $line);
            }
        }

        return Command::SUCCESS;
    }

    private function resolveTargetSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rows = $sheet->toArray(null, true, true, true);
            $headers = array_map(static fn (mixed $value): string => trim((string) $value), $rows[1] ?? []);
            if (
                $this->findColumnLetter($headers, ['joueur']) !== null
                && $this->findColumnLetter($headers, ['num ext', 'num']) !== null
            ) {
                return $sheet;
            }
        }

        return null;
    }

    private function warmupCountryCache(): void
    {
        $rows = $this->db->fetchAllAssociative('SELECT id, name FROM country');
        foreach ($rows as $row) {
            $name = $this->toNullableString($row['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $this->countryByNormalizedName[$this->normalize($name)] = (int) $row['id'];
        }
    }

    private function warmupPositionCache(): void
    {
        $rows = $this->db->fetchAllAssociative('SELECT id, code, label FROM position');
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $code = $this->toNullableString($row['code'] ?? null);
            $label = $this->toNullableString($row['label'] ?? null);

            if ($code !== null) {
                $this->positionByNormalizedLabelOrCode[$this->normalize($code)] = $id;
            }
            if ($label !== null) {
                $this->positionByNormalizedLabelOrCode[$this->normalize($label)] = $id;
            }
        }
    }

    private function warmupPlayerCache(): void
    {
        $rows = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT pl.id AS player_id, p.id AS person_id, p.full_name
                FROM player pl
                INNER JOIN person p ON p.id = pl.person_id
            SQL
        );

        foreach ($rows as $row) {
            $fullName = $this->toNullableString($row['full_name'] ?? null);
            if ($fullName === null) {
                continue;
            }

            $this->playerByNormalizedName[$this->normalize($fullName)] = [
                'playerId' => (int) $row['player_id'],
                'personId' => (int) $row['person_id'],
                'fullName' => $fullName,
            ];
        }
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $row
     * @param array<int, string> $aliases
     */
    private function getByHeader(array $headers, array $row, array $aliases): mixed
    {
        $columnLetter = $this->findColumnLetter($headers, $aliases);
        return $columnLetter === null ? null : ($row[$columnLetter] ?? null);
    }

    /**
     * @param array<string, string> $headers
     * @param array<int, string> $aliases
     */
    private function findColumnLetter(array $headers, array $aliases): ?string
    {
        $normalizedAliases = array_map(fn (string $name): string => $this->normalize($name), $aliases);

        foreach ($headers as $column => $headerValue) {
            if (\in_array($this->normalize($headerValue), $normalizedAliases, true)) {
                return (string) $column;
            }
        }

        return null;
    }

    private function toNullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : preg_replace('/\s+/u', ' ', $text);
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $clean = str_replace([' ', ','], ['', '.'], $text);
        return is_numeric($clean) ? (int) round((float) $clean) : null;
    }

    private function normalize(string $value): string
    {
        $text = mb_strtolower(trim($value));
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii !== false) {
            $text = $ascii;
        }

        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return $text;
    }

    private function parseSpreadsheetDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $text = $this->toNullableString($value);
        if ($text === null) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $match)) {
            return sprintf('%04d-%02d-%02d', (int) $match[1], (int) $match[2], (int) $match[3]);
        }

        if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $text, $match)) {
            $first = (int) $match[1];
            $second = (int) $match[2];
            $year = (int) $match[3];

            if ($first > 12 && $second <= 12) {
                return sprintf('%04d-%02d-%02d', $year, $second, $first);
            }

            if ($second > 12 && $first <= 12) {
                return sprintf('%04d-%02d-%02d', $year, $first, $second);
            }

            return sprintf('%04d-%02d-%02d', $year, $second, $first);
        }

        return null;
    }

    private function parseDeathDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = preg_replace('/^d[ée]c[ée]d[ée]?\s+le\s+/iu', '', $value) ?? $value;
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $parsed = $this->parseSpreadsheetDate($text);
        if ($parsed !== null) {
            return $parsed;
        }

        $normalized = $this->normalize($text);
        if (!preg_match('/^(\d{1,2})\s+([a-z]+)\s+(\d{4})$/', $normalized, $match)) {
            return null;
        }

        $day = (int) $match[1];
        $monthName = $match[2];
        $year = (int) $match[3];
        $months = [
            'janvier' => 1,
            'fevrier' => 2,
            'mars' => 3,
            'avril' => 4,
            'mai' => 5,
            'juin' => 6,
            'juillet' => 7,
            'aout' => 8,
            'septembre' => 9,
            'octobre' => 10,
            'novembre' => 11,
            'decembre' => 12,
        ];

        if (!isset($months[$monthName])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $months[$monthName], $day);
    }

    /** @return array<int, string>|null */
    private function parseMainClubs(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $parts = array_map(
            fn (string $part): string => trim($part),
            explode('/', $value)
        );
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        return $parts === [] ? null : $parts;
    }

    private function resolvePositionId(?string $positionLabel): ?int
    {
        if ($positionLabel === null) {
            return null;
        }

        $normalized = $this->normalize($positionLabel);
        if ($normalized === '') {
            return null;
        }

        $direct = $this->positionByNormalizedLabelOrCode[$normalized] ?? null;
        if ($direct !== null) {
            return $direct;
        }

        $code = null;
        if (str_contains($normalized, 'gardien')) {
            $code = 'gk';
        } elseif (str_contains($normalized, 'defenseur') || str_contains($normalized, 'defense') || str_contains($normalized, 'arriere')) {
            $code = 'def';
        } elseif (str_contains($normalized, 'milieu')) {
            $code = 'mid';
        } elseif (str_contains($normalized, 'attaquant') || str_contains($normalized, 'avant') || str_contains($normalized, 'ailier')) {
            $code = 'att';
        }

        return $code === null ? null : ($this->positionByNormalizedLabelOrCode[$code] ?? null);
    }

    /**
     * @param array<string, int> $stats
     * @return array<string, mixed>
     */
    private function resolveLocationUpdates(?string $wilaya, ?string $birthPlace, array &$stats): array
    {
        if ($wilaya === null) {
            return [];
        }

        $countryIdFromWilaya = $this->countryByNormalizedName[$this->normalize($wilaya)] ?? null;
        if ($countryIdFromWilaya !== null) {
            $stats['country_wilaya_detected']++;
            $regionId = $birthPlace === null ? null : $this->ensureRegion($birthPlace, $countryIdFromWilaya);

            return [
                'birth_country_id' => $countryIdFromWilaya,
                'birth_region_id' => $regionId,
                'birth_city_id' => null,
            ];
        }

        $stats['algeria_wilaya_detected']++;
        $algeriaId = $this->ensureAlgeriaCountry();
        $regionId = $this->ensureRegion($wilaya, $algeriaId);
        $cityId = $birthPlace === null ? null : $this->ensureCity($birthPlace, $algeriaId, $regionId);

        return [
            'birth_country_id' => $algeriaId,
            'birth_region_id' => $regionId,
            'birth_city_id' => $cityId,
        ];
    }

    private function ensureAlgeriaCountry(): int
    {
        foreach (['algerie', 'algeria', 'algerie democratique et populaire'] as $name) {
            if (isset($this->countryByNormalizedName[$name])) {
                return $this->countryByNormalizedName[$name];
            }
        }

        $this->db->insert('country', [
            'name' => 'Algérie',
            'iso2' => null,
            'iso3' => null,
            'fifa_code' => null,
        ]);
        $id = (int) $this->db->lastInsertId();
        $this->countryByNormalizedName[$this->normalize('Algérie')] = $id;
        $this->createdCountries++;

        return $id;
    }

    private function ensureRegion(string $name, int $countryId): int
    {
        $normalized = $this->normalize($name);
        $cached = $this->regionByNormalizedName[$normalized] ?? null;
        if ($cached !== null) {
            $this->db->update('region', ['country_id' => $countryId], ['id' => $cached]);
            return $cached;
        }

        $existing = $this->db->fetchAssociative(
            'SELECT id, country_id FROM region WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) ORDER BY id ASC LIMIT 1',
            ['name' => $name]
        );

        if ($existing !== false) {
            $regionId = (int) $existing['id'];
            if ((int) ($existing['country_id'] ?? 0) !== $countryId) {
                $this->db->update('region', ['country_id' => $countryId], ['id' => $regionId]);
            }
            $this->regionByNormalizedName[$normalized] = $regionId;

            return $regionId;
        }

        $this->db->insert('region', [
            'country_id' => $countryId,
            'name' => trim($name),
            'type' => null,
        ]);
        $regionId = (int) $this->db->lastInsertId();
        $this->regionByNormalizedName[$normalized] = $regionId;
        $this->createdRegions++;

        return $regionId;
    }

    private function ensureCity(string $name, int $countryId, ?int $regionId): int
    {
        $normalized = $this->normalize($name);
        $cached = $this->cityByNormalizedName[$normalized] ?? null;
        if ($cached !== null) {
            $this->db->update('city', ['country_id' => $countryId, 'region_id' => $regionId], ['id' => $cached]);
            return $cached;
        }

        $existing = $this->db->fetchAssociative(
            'SELECT id, country_id, region_id FROM city WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) ORDER BY id ASC LIMIT 1',
            ['name' => $name]
        );

        if ($existing !== false) {
            $cityId = (int) $existing['id'];
            if ((int) ($existing['country_id'] ?? 0) !== $countryId || (int) ($existing['region_id'] ?? 0) !== (int) $regionId) {
                $this->db->update('city', ['country_id' => $countryId, 'region_id' => $regionId], ['id' => $cityId]);
            }
            $this->cityByNormalizedName[$normalized] = $cityId;

            return $cityId;
        }

        $this->db->insert('city', [
            'country_id' => $countryId,
            'region_id' => $regionId,
            'name' => trim($name),
        ]);
        $cityId = (int) $this->db->lastInsertId();
        $this->cityByNormalizedName[$normalized] = $cityId;
        $this->createdCities++;

        return $cityId;
    }

    private function parseCareer(string $careerRaw): ?string
    {
        if ($careerRaw === '') {
            return null;
        }

        $career = str_replace(',', '.', $careerRaw);

        return $career;
    }
}
