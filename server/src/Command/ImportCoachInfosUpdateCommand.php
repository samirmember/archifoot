<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import:coach-infos-update',
    description: 'Importe les informations entraîneurs depuis coach-infos.xlsx et met à jour person/coach.'
)]
class ImportCoachInfosUpdateCommand extends Command
{
    /** @var array<string, int> */
    private array $countryByNormalizedName = [];

    /** @var array<string, int> */
    private array $regionByLookupKey = [];

    /** @var array<string, int> */
    private array $cityByLookupKey = [];

    /** @var array<string, array<int, array{coachId:int, personId:int, fullName:string}>> */
    private array $coachesByNormalizedName = [];

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
            ->addArgument('file', InputArgument::OPTIONAL, 'Chemin du fichier xlsx', 'coach-infos.xlsx')
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
            $output->writeln('<error>Aucune feuille compatible trouvée.</error>');
            return Command::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (\count($rows) < 2) {
            $output->writeln('<error>Le fichier ne contient pas de données à importer.</error>');
            return Command::FAILURE;
        }

        $this->warmupCountryCache();
        $this->warmupCoachCache();

        $stats = [
            'rows' => 0,
            'updated' => 0,
            'missing' => 0,
            'ambiguous' => 0,
            'ignored' => 0,
            'invalid_birth_date' => 0,
            'invalid_death_date' => 0,
        ];

        /** @var array<int, string> $notFoundCoaches */
        $notFoundCoaches = [];
        /** @var array<int, string> $ambiguousCoaches */
        $ambiguousCoaches = [];

        $this->db->beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index === 1) {
                    continue;
                }

                $stats['rows']++;

                $fullName = $this->toNullableString($row['A'] ?? null);
                if ($fullName === null) {
                    $stats['ignored']++;
                    continue;
                }

                $matches = $this->coachesByNormalizedName[$this->normalize($fullName)] ?? [];
                if ($matches === []) {
                    $stats['missing']++;
                    $notFoundCoaches[] = $fullName;
                    continue;
                }

                if (\count($matches) > 1) {
                    $stats['ambiguous']++;
                    $ambiguousCoaches[] = $fullName;
                    continue;
                }

                $coachData = $matches[0];

                $birthDateRaw = $row['B'] ?? null;
                $deathDateRaw = $row['F'] ?? null;

                $birthDate = $this->parseSpreadsheetDate($birthDateRaw);
                if ($birthDate === null && $this->toNullableString($birthDateRaw) !== null) {
                    $stats['invalid_birth_date']++;
                }

                $deathDate = $this->parseSpreadsheetDate($deathDateRaw);
                if ($deathDate === null && $this->toNullableString($deathDateRaw) !== null) {
                    $stats['invalid_death_date']++;
                }

                $birthLocation = $this->resolveLocation(
                    $this->toNullableString($row['C'] ?? null),
                    $this->toNullableString($row['D'] ?? null),
                    $this->toNullableString($row['E'] ?? null)
                );

                $deathLocation = $this->resolveLocation(
                    $this->toNullableString($row['G'] ?? null),
                    $this->toNullableString($row['H'] ?? null),
                    $this->toNullableString($row['I'] ?? null)
                );

                $this->db->update('person', [
                    'birth_date' => $birthDate,
                    'birth_city_id' => $birthLocation['city_id'],
                    'birth_region_id' => $birthLocation['region_id'],
                    'birth_country_id' => $birthLocation['country_id'],
                    'death_date' => $deathDate,
                ], ['id' => $coachData['personId']]);

                $mainClubs = $this->parseMainClubs($this->toNullableString($row['K'] ?? null));

                $this->db->update('coach', [
                    'death_city_id' => $deathLocation['city_id'],
                    'death_region_id' => $deathLocation['region_id'],
                    'death_country_id' => $deathLocation['country_id'],
                    'career' => $this->toNullableString($row['J'] ?? null),
                    'main_clubs' => $mainClubs === null ? null : json_encode($mainClubs, JSON_UNESCAPED_UNICODE),
                    'algeria_player_caps' => $this->toNullableInt($row['L'] ?? null),
                    'foreign_player_caps' => $this->toNullableInt($row['M'] ?? null),
                    'head_matches' => $this->toNullableInt($row['N'] ?? null),
                    'callups' => $this->toNullableInt($row['O'] ?? null),
                    'assistant_matches' => $this->toNullableInt($row['P'] ?? null),
                    'bio' => $this->toNullableText($row['Q'] ?? null),
                ], ['id' => $coachData['coachId']]);

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
        $output->writeln(sprintf('Coachs mis à jour: %d', $stats['updated']));
        $output->writeln(sprintf('Coachs introuvables: %d', $stats['missing']));
        $output->writeln(sprintf('Coachs ambigus: %d', $stats['ambiguous']));
        $output->writeln(sprintf('Lignes ignorées: %d', $stats['ignored']));
        $output->writeln(sprintf('Dates de naissance invalides: %d', $stats['invalid_birth_date']));
        $output->writeln(sprintf('Dates de décès invalides: %d', $stats['invalid_death_date']));
        $output->writeln(sprintf('Pays créés: %d', $this->createdCountries));
        $output->writeln(sprintf('Régions créées: %d', $this->createdRegions));
        $output->writeln(sprintf('Villes créées: %d', $this->createdCities));

        if ($notFoundCoaches !== []) {
            $output->writeln('');
            $output->writeln('<comment>Entraîneurs introuvables (ignorés):</comment>');
            foreach (array_values(array_unique($notFoundCoaches)) as $coachName) {
                $output->writeln(' - ' . $coachName);
            }
        }

        if ($ambiguousCoaches !== []) {
            $output->writeln('');
            $output->writeln('<comment>Entraîneurs ambigus (ignorés):</comment>');
            foreach (array_values(array_unique($ambiguousCoaches)) as $coachName) {
                $output->writeln(' - ' . $coachName);
            }
        }

        return Command::SUCCESS;
    }

    private function resolveTargetSheet(Spreadsheet $spreadsheet): ?Worksheet
    {
        $namedSheet = $spreadsheet->getSheetByName('entraineurs');
        if ($namedSheet instanceof Worksheet) {
            return $namedSheet;
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $headerA = $this->normalize((string) ($sheet->getCell('A1')->getValue() ?? ''));
            $headerQ = $this->normalize((string) ($sheet->getCell('Q1')->getValue() ?? ''));
            if ($headerA === 'nom complet' && $headerQ === 'description') {
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

    private function warmupCoachCache(): void
    {
        $rows = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT c.id AS coach_id, p.id AS person_id, p.full_name
                FROM coach c
                INNER JOIN person p ON p.id = c.person_id
            SQL
        );

        foreach ($rows as $row) {
            $fullName = $this->toNullableString($row['full_name'] ?? null);
            if ($fullName === null) {
                continue;
            }

            $normalized = $this->normalize($fullName);
            $this->coachesByNormalizedName[$normalized][] = [
                'coachId' => (int) $row['coach_id'],
                'personId' => (int) $row['person_id'],
                'fullName' => $fullName,
            ];
        }
    }

    /**
     * @return array{country_id:?int,region_id:?int,city_id:?int}
     */
    private function resolveLocation(?string $cityName, ?string $regionName, ?string $countryName): array
    {
        $countryId = $countryName === null ? null : $this->ensureCountry($countryName);
        $regionId = $regionName === null ? null : $this->ensureRegion($regionName, $countryId);
        $cityId = $cityName === null ? null : $this->ensureCity($cityName, $countryId, $regionId);

        return [
            'country_id' => $countryId,
            'region_id' => $regionId,
            'city_id' => $cityId,
        ];
    }

    private function ensureCountry(string $name): int
    {
        $normalized = $this->normalize($name);
        $cached = $this->countryByNormalizedName[$normalized] ?? null;
        if ($cached !== null) {
            return $cached;
        }

        $existing = $this->db->fetchAssociative(
            'SELECT id FROM country WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) ORDER BY id ASC LIMIT 1',
            ['name' => $name]
        );

        if ($existing !== false) {
            $countryId = (int) $existing['id'];
            $this->countryByNormalizedName[$normalized] = $countryId;

            return $countryId;
        }

        $this->db->insert('country', [
            'name' => trim($name),
            'iso2' => null,
            'iso3' => null,
            'fifa_code' => null,
        ]);
        $countryId = (int) $this->db->lastInsertId();
        $this->countryByNormalizedName[$normalized] = $countryId;
        $this->createdCountries++;

        return $countryId;
    }

    private function ensureRegion(string $name, ?int $countryId): int
    {
        $lookupKey = $this->buildLookupKey($name, $countryId);
        $cached = $this->regionByLookupKey[$lookupKey] ?? null;
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT id, country_id FROM region WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))';
        $params = ['name' => $name];

        if ($countryId !== null) {
            $sql .= ' AND ((country_id = :countryId) OR country_id IS NULL)';
            $params['countryId'] = $countryId;
        }

        $sql .= ' ORDER BY CASE WHEN country_id = :countrySort THEN 0 ELSE 1 END, id ASC LIMIT 1';
        $params['countrySort'] = $countryId;

        $existing = $this->db->fetchAssociative($sql, $params);

        if ($existing !== false) {
            $regionId = (int) $existing['id'];
            if ($countryId !== null && (int) ($existing['country_id'] ?? 0) !== $countryId) {
                $this->db->update('region', ['country_id' => $countryId], ['id' => $regionId]);
            }
            $this->regionByLookupKey[$lookupKey] = $regionId;

            return $regionId;
        }

        $this->db->insert('region', [
            'country_id' => $countryId,
            'name' => trim($name),
            'type' => null,
        ]);
        $regionId = (int) $this->db->lastInsertId();
        $this->regionByLookupKey[$lookupKey] = $regionId;
        $this->createdRegions++;

        return $regionId;
    }

    private function ensureCity(string $name, ?int $countryId, ?int $regionId): int
    {
        $lookupKey = $this->buildLookupKey($name, $countryId, $regionId);
        $cached = $this->cityByLookupKey[$lookupKey] ?? null;
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT id, country_id, region_id FROM city WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))';
        $params = ['name' => $name];

        if ($countryId !== null) {
            $sql .= ' AND ((country_id = :countryId) OR country_id IS NULL)';
            $params['countryId'] = $countryId;
        }

        if ($regionId !== null) {
            $sql .= ' AND ((region_id = :regionId) OR region_id IS NULL)';
            $params['regionId'] = $regionId;
        }

        $sql .= ' ORDER BY'
            . ' CASE WHEN country_id = :countrySort THEN 0 ELSE 1 END,'
            . ' CASE WHEN region_id = :regionSort THEN 0 ELSE 1 END,'
            . ' id ASC LIMIT 1';
        $params['countrySort'] = $countryId;
        $params['regionSort'] = $regionId;

        $existing = $this->db->fetchAssociative($sql, $params);

        if ($existing !== false) {
            $cityId = (int) $existing['id'];
            $updates = [];
            if ($countryId !== null && (int) ($existing['country_id'] ?? 0) !== $countryId) {
                $updates['country_id'] = $countryId;
            }
            if ((int) ($existing['region_id'] ?? 0) !== (int) $regionId) {
                $updates['region_id'] = $regionId;
            }
            if ($updates !== []) {
                $this->db->update('city', $updates, ['id' => $cityId]);
            }
            $this->cityByLookupKey[$lookupKey] = $cityId;

            return $cityId;
        }

        $this->db->insert('city', [
            'country_id' => $countryId,
            'region_id' => $regionId,
            'name' => trim($name),
        ]);
        $cityId = (int) $this->db->lastInsertId();
        $this->cityByLookupKey[$lookupKey] = $cityId;
        $this->createdCities++;

        return $cityId;
    }

    private function buildLookupKey(string $name, ?int $countryId = null, ?int $regionId = null): string
    {
        return implode('|', [
            $this->normalize($name),
            (string) ($countryId ?? 'null'),
            (string) ($regionId ?? 'null'),
        ]);
    }

    private function toNullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : preg_replace('/\s+/u', ' ', $text);
    }

    private function toNullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
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

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $match)) {
            return sprintf('%04d-%02d-%02d', (int) $match[1], (int) $match[2], (int) $match[3]);
        }

        if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $text, $match)) {
            return sprintf('%04d-%02d-%02d', (int) $match[3], (int) $match[2], (int) $match[1]);
        }

        return null;
    }

    /** @return array<int, string>|null */
    private function parseMainClubs(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $value));
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        return $parts === [] ? null : $parts;
    }
}
