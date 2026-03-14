<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import:en:matches-scoresheets',
    description: "Importe '01 EN seniors - Matches' vers fixture + participants + scoresheet + goals + subs + lineups."
)]
class ImportEnMatchesAndScoresheetsCommand extends Command
{
    private int $seasonId;

    public function __construct(private readonly Connection $db) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin xlsx (dans le conteneur)')
            ->addArgument('sheet', InputArgument::OPTIONAL, 'Nom de la feuille', "Matches de l'EN ");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string)$input->getArgument('file');
        $sheetName = (string)$input->getArgument('sheet');

        if (!is_file($file)) {
            $output->writeln("<error>Fichier introuvable: $file</error>");
            return Command::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if ($sheet === null) {
            $output->writeln("<error>Feuille introuvable: '$sheetName'</error>");
            return Command::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true); // A,B,C...
        if (count($rows) < 2) {
            $output->writeln("<error>Feuille vide</error>");
            return Command::FAILURE;
        }

        $headers = $this->buildHeaderMap($rows[1]);
        $seniorCategoryId = $this->ensureCategory('Sénior', 'M');

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $this->db->beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                if ($i === 1) continue;

                $matchNo = $this->toInt($this->get($row, $headers, ['n° du match','no match','match no','n du match']));
                if ($matchNo === null) continue;

                $output->writeln(sprintf(
                    '<info>Traitement ligne %d: %s</info>',
                    $i,
                    json_encode($row, JSON_UNESCAPED_UNICODE)
                ));

                $competitionIds = $this->ensureCompetitions($row['B'], $seniorCategoryId);
                $seasonId = $this->ensureSeason($row['D']);
                $this->seasonId = $seasonId;
                $editionIds = $this->ensureEditions($competitionIds, $seasonId, $row['C']);
                $stageIds = $this->ensureStages($editionIds, $row['E']);

                $dateVal = $this->get($row, $headers, ['date']);
                $matchDate = $this->parseDateToYmd($dateVal); // YYYY-MM-DD ou null

                $categoryIdA = $this->ensureCategory($row['J'], 'M');
                $categoryIdB = $this->ensureCategory($row['O'], 'M');
                
                $teamAName = $this->toStr($this->get($row, $headers, ['pays a','equipe a','équipe a']));
                $teamBName = $this->toStr($this->get($row, $headers, ['pays b','equipe b','équipe b']));
                if (!$teamAName || !$teamBName) {
                    $skipped++;
                    continue;
                }

                $scoreA = $this->toInt($this->get($row, $headers, ['buts a','but a','score a']));
                $scoreB = $this->toInt($this->get($row, $headers, ['buts b','but b','score b']));

                $played = 1; // par défaut joué
                $isOfficial = $row['B'] !== 'Match Amical' ? 1 : 0;
                $notes = $row['P'] ?? null;
                $internalNotes = $row['DC'] ?? null;

                // Venue
                $stadiumName = $this->toStr($this->get($row, $headers, ['stade','stade ']));
                $cityName = $this->toStr($this->get($row, $headers, ['ville -stade','ville','ville stade']));
                $venueCountryName = $this->toStr($this->get($row, $headers, ['pays -stade','pays stade','pays']));
                $countryId = $venueCountryName ? $this->ensureCountry($venueCountryName) : null;
                $cityId = $this->ensureCity($cityName, $venueCountryName);
                $stadiumId = $stadiumName ? $this->ensureStadium($stadiumName, $cityId, $countryId) : null;

                // === Fixture + Participants ===
                $fixtureId = $this->upsertFixture(
                    $output,
                    $matchNo,
                    $seasonId,
                    $seniorCategoryId,
                    $matchDate,
                    $stadiumId,
                    $cityId,
                    $countryId,
                    $played,
                    $isOfficial,
                    $notes,
                    $internalNotes,
                    $competitionIds,
                    $editionIds,
                    $stageIds
                );

                // Teams A/B (national teams via country)
                $teamAId = $this->ensureNationalTeamAsTeam($teamAName, $categoryIdA);
                $teamBId = $this->ensureNationalTeamAsTeam($teamBName, $categoryIdB);

                $this->upsertFixtureParticipant($fixtureId, $teamAId, 'A', $scoreA, null);
                $this->upsertFixtureParticipant($fixtureId, $teamBId, 'B', $scoreB, null);

                // @Todo: ensureReferee: récupérer plusieurs arbitres dans le même champ séparé par des virgules
                // Cas: Nicholas Haïni, Didamanti, Raphael Zader, tous de nationalité suisse

                $algeriaTeamId = $this->resolveAlgeriaTeamId($teamAName, $teamBName, $teamAId, $teamBId);
                $otherTeamId = $algeriaTeamId === $teamAId ? $teamBId : $teamAId;

                $dzTeamCountryId = $this->getCountryByTeamId($algeriaTeamId);
                $otherTeamCountryId = $this->getCountryByTeamId($otherTeamId);

                // Entraineur principal
                $coach = $this->toStr($row['BJ'] ?? null);
                $coachPersonId = $coach ? $this->ensureCoachPerson($coach, 'HEAD_COACH', $dzTeamCountryId) : null;
                $coachAdv = $this->toStr($row['BK'] ?? null);
                $coachAdvPersonId = $coachAdv ? $this->ensureCoachPerson($coachAdv, 'HEAD_COACH', $otherTeamCountryId) : null;
                // $coachAdvAssist = $this->toStr($row['BL'] ?? null);
                // $coachAdvAssistantPersonId = $coachAdvAssist ? $this->ensureCoachPerson($coachAdvAssist, 'ASSISTANT_COACH', $otherTeamCountryId) : null;

                // Entraineurs assistants 1 et 2
                $coachAssistDz1 = $this->toStr($row['BM'] ?? null);
                $coachAssistDz2 = $this->toStr($row['BN'] ?? null);
                $coachAssistantDz1PersonId = $coachAssistDz1 ? $this->ensureCoachPerson($coachAssistDz1, 'ASSISTANT_COACH', $dzTeamCountryId) : null;
                $coachAssistantDz2PersonId = $coachAssistDz2 ? $this->ensureCoachPerson($coachAssistDz2, 'ASSISTANT_COACH', $dzTeamCountryId) : null;

                $scoresheetId = $this->ensureScoresheet($fixtureId, [
                    'attendance' => $this->toInt($this->get($row, $headers, ['nbr_spectateur','spectateur','attendance'])),
                    'fixed_time' => $this->toStr($this->get($row, $headers, ['heure_fixe'])),
                    'kickoff_time' => $this->toStr($this->get($row, $headers, ['heure_coup_denvoi','heure coup denvoi','kickoff'])),
                    'half_time' => $this->toStr($this->get($row, $headers, ['heure_pause'])),
                    'second_half_start' => $this->toStr($this->get($row, $headers, ['heure_reprise'])),
                    'full_time' => $this->toStr($this->get($row, $headers, ['heure_fin'])),
                    'stoppage_time' => $this->toStr($this->get($row, $headers, ['heure_arret'])),
                    'match_stop_time' => null,
                    'reservations' => $this->toStr($this->get($row, $headers, ['reserve','réserve','reservations'])),
                    'report' => null,
                    'signed_place' => $this->toStr($this->get($row, $headers, ['fait_a','fait à','fait a'])),
                    'signed_on' => $this->parseDateToYmd($this->get($row, $headers, ['le'])),
                    'status' => '1',
                ]);

                // === Lineups (j1..j20 for Algeria and adv) ===
                $this->importLineups($scoresheetId, $matchDate, $row, $algeriaTeamId, $otherTeamId);

                // Les coachs
                $this->importCoaches($scoresheetId, $matchDate, $row, $algeriaTeamId, $otherTeamId);

                // === Substitutions (chang1..5 for Algeria and adv) ===
                $this->importSubstitutions($scoresheetId, $matchDate, $row, $algeriaTeamId, $otherTeamId);

                // === Goals (but1..butN, but_local/but_visiteur) ===
                $this->importGoals($fixtureId, $algeriaTeamId, $otherTeamId, $matchDate, $row);

                // === Referees (arbitre1..3) ===
                $this->importReferees($scoresheetId, $matchDate, $row);

                $this->importAdvGoals($fixtureId, $algeriaTeamId, $otherTeamId, $matchDate, $row);

                $inserted++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $output->writeln("<error>Erreur import: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>OK fixtures traités=$inserted skipped=$skipped</info>");
        return Command::SUCCESS;
    }

    private function importCoaches(int $scoresheetId, string $matchDate, array $row, int $algeriaTeamId, int $otherTeamId): void
    {
        $dzTeamCountryId = $this->getCountryByTeamId($algeriaTeamId);
        $otherTeamCountryId = $this->getCountryByTeamId($otherTeamId);

        $coach = $this->toStr($row['BJ'] ?? null);
        if ($coach) {
            $role = 'HEAD_COACH';
            $coachId = $this->ensureCoachPerson($coach, $role, $dzTeamCountryId);
            $personId = $this->getPersonIdByCoachId($coachId);
            $this->ensurePersonAssignmentRecord($personId, $matchDate, 2); // 2 = Entraineur
            $scoresheetLineupId = $this->ensureScoresheetStaffRecord($scoresheetId, $personId, $role, $algeriaTeamId);
        }

        $coachAssistant1 = $this->toStr($row['BM'] ?? null);
        if ($coachAssistant1) {
            $role = 'ASSISTANT_COACH';
            $coachId = $this->ensureCoachPerson($coachAssistant1, $role, $dzTeamCountryId);
            $personId = $this->getPersonIdByCoachId($coachId);
            $this->ensurePersonAssignmentRecord($personId, $matchDate, 3); // 3 = Entraineur Assistant
            $scoresheetLineupId = $this->ensureScoresheetStaffRecord($scoresheetId, $personId, $role, $algeriaTeamId);
        }

        $coachAssistant2 = $this->toStr($row['BN'] ?? null);
        if ($coachAssistant2) {
            $role = 'ASSISTANT_COACH';
            $coachId = $this->ensureCoachPerson($coachAssistant2, $role, $dzTeamCountryId);
            $personId = $this->getPersonIdByCoachId($coachId);
            $this->ensurePersonAssignmentRecord($personId, $matchDate, 3); // 3 = Entraineur Assistant
            $scoresheetLineupId = $this->ensureScoresheetStaffRecord($scoresheetId, $personId, $role, $algeriaTeamId);
        }

        $coach = $this->toStr($row['BK'] ?? null);
        if ($coach) {
            $role = 'HEAD_COACH';
            $coachId = $this->ensureCoachPerson($coach, $role, $otherTeamCountryId);
            $personId = $this->getPersonIdByCoachId($coachId);
            $this->ensurePersonAssignmentRecord($personId, $matchDate, 2); // 2 = Entraineur
            $scoresheetLineupId = $this->ensureScoresheetStaffRecord($scoresheetId, $personId, $role, $otherTeamId);
        }

        // $coachAdvAssist = $this->toStr($row['BL'] ?? null);
        // if ($coachAdvAssist) {
        //     $role = 'ASSISTANT_COACH';
        //     $coachId = $this->ensureCoachPerson($coachAdvAssist, $role, $otherTeamCountryId);
        //     $this->ensurePersonAssignmentRecord($coachId, $matchDate, 3); // 3 = Entraineur Assistant
        //     $personId = $this->getPersonIdByCoachId($coachId);
        //     $scoresheetLineupId = $this->ensureScoresheetStaffRecord($scoresheetId, $personId, $role, $otherTeamId);
        // }
    }

    // ----------------- Helpers (header + parsing) -----------------

    private function buildHeaderMap(array $headerRow): array
    {
        $map = []; // normalized => columnLetter
        foreach ($headerRow as $col => $label) {
            $n = $this->norm((string)$label);
            if ($n !== '') $map[$n] = $col;
        }
        return $map;
    }

    private function get(array $row, array $headers, array $names): mixed
    {
        foreach ($names as $name) {
            $k = $this->norm($name);
            if (isset($headers[$k])) {
                return $row[$headers[$k]] ?? null;
            }
        }
        return null;
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['é','è','ê','ë','à','â','ä','î','ï','ô','ö','ù','û','ü','ç','°','’'], ['e','e','e','e','a','a','a','i','i','o','o','u','u','u','c','','\''], $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return $s ?? '';
    }

    private function toStr(mixed $v): ?string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null) return null;
        if (is_numeric($v)) return (int)$v;
        $s = trim((string)$v);
        if ($s === '') return null;
        if (!preg_match('/^-?\d+$/', $s)) return null;
        return (int)$s;
    }

    private function toBool01(?string $s): int
    {
        if ($s === null) return 0;
        $x = mb_strtolower(trim($s));
        if ($x === '1' || $x === 'oui' || $x === 'yes' || $x === 'true') return 1;
        if ($x === '0' || $x === 'non' || $x === 'no' || $x === 'false') return 0;
        // fallback: si "match officiel" est vide -> 0
        return (int)preg_match('/1|oui|yes|true/', $x);
    }

    private function parseDateToYmd(mixed $v): ?string
    {
        if ($v === null) return null;

        // Excel numeric date
        if (is_numeric($v)) {
            // PhpSpreadsheet fournit souvent déjà string; ici best-effort sans calcul OA date
            return null;
        }

        $s = trim((string)$v);
        if ($s === '') return null;

        // formats fréquents: dd/mm/yyyy, yyyy-mm-dd
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $s, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})#', $s, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        $date = \DateTimeImmutable::createFromFormat('!d/m/Y', $s)
        ?: \DateTimeImmutable::createFromFormat('!d/m/y', $s);

        if ($date) {
            return $date->format('Y-m-d');
        }

        return null;
    }

    private function resolveAlgeriaTeamId(string $teamAName, string $teamBName, int $teamAId, int $teamBId): ?int
    {
        $a = $this->norm($teamAName);
        $b = $this->norm($teamBName);
        if ($a !== '' && str_contains($a, 'algerie')) return $teamAId;
        if ($b !== '' && str_contains($b, 'algerie')) return $teamBId;
        return null;
    }

    private function mergeNotes(?string ...$parts): ?string
    {
        $out = [];
        foreach ($parts as $part) {
            $p = $this->toStr($part);
            if ($p !== null) $out[] = $p;
        }
        if ($out === []) return null;
        return implode("\n", $out);
    }

    // ----------------- Import blocks: lineups/subs/goals -----------------

    private function importLineups(int $scoresheetId, string $matchDate, array $row, int $algeriaTeamId, int $otherTeamId): void
    {
        $lineupsCols = [
            'Q', 'R', 'S', 'T', 'U', 'V',
            'W', 'X', 'Y', 'Z', 'AA', 'AB',
            'AC', 'AD', 'AE', 'AF', 'AG',
            'AH', 'AI', 'AJ'
        ];
        $i = 1;

        $dzTeamCountryId = $this->getCountryByTeamId($algeriaTeamId);
        $otherTeamCountryId = $this->getCountryByTeamId($otherTeamId);

        $captainColDZ = 'BO';
        $captainColAdv = 'BP';

        foreach ($lineupsCols as $colKey) {
            $val = $this->toStr($row[$colKey] ?? null);
            if (!$val) continue;
            $isDZCaptain = $val === $this->toStr($row[$captainColDZ] ?? null);          
            $role = ($i <= 11) ? 'STARTER' : 'SUB';
            $playerId = $this->ensurePlayerPerson($val, $dzTeamCountryId);
            $personId = $this->getPersonIdByPlayerId($playerId);
            $this->ensurePersonAssignmentRecord($personId, $matchDate);
            $scoresheetLineupId = $this->ensureScoresheetLineupRecord($scoresheetId, $playerId, $val, $role, $i, $algeriaTeamId, $isDZCaptain);
            $i++;
        }

        $advLineupsCols = [
            'BY', 'BZ', 'CA', 'CB', 'CC', 'CD',
            'CE', 'CF', 'CG', 'CH', 'CI', 'CJ',
            'CK', 'CL', 'CM', 'CN', 'CO',
            'CP', 'CQ', 'CR'
        ];

        $i = 1;
        foreach ($advLineupsCols as $colKey) {
            $val = $this->toStr($row[$colKey] ?? null);
            if (!$val) continue;
            $isAdvCaptain = $val === $this->toStr($row[$captainColAdv] ?? null);
            $role = ($i <= 11) ? 'STARTER' : 'SUB';
            $playerId = $this->ensurePlayerPerson($val, $otherTeamCountryId);
            $scoresheetLineupId = $this->ensureScoresheetLineupRecord($scoresheetId, $playerId, $val, $role, $i, $otherTeamId, $isAdvCaptain);
            $i++;
        }
    }

    private function ensurePersonAssignmentRecord(int $personId, string $matchDate, int $roleId = 1): int
    {
        $year = (int)substr($matchDate, 0, 4);
        $playerData = [
            'person_id' => $personId,
            'team_id' => 1, // Algérie par défaut, on sauvegarde que l'historique pour l'Algérie.
            'from_date' => "{$year}-01-01",
            'to_date' => "{$year}-12-31",
            'season_id' => $this->seasonId,
            'role_id' => $roleId,
        ];

        $id = $this->db->fetchOne("SELECT id FROM person_assignment 
            WHERE person_id = :person_id 
            AND team_id = :team_id
            AND from_date = :from_date
            AND to_date = :to_date
            AND season_id = :season_id
            AND role_id = :role_id
            ", $playerData
        );
        if ($id) return (int)$id;

        $this->db->insert('person_assignment', $playerData);
        return (int)$this->db->lastInsertId();
    }

    private function ensureScoresheetLineupRecord(int $scoresheetId, int $playerId, ?string $val, string $role, int $i, int $teamId = 1, bool $isCaptain): int
    {
        $playerLineUp = [
            'scoresheet_id' => $scoresheetId,
            'team_id' => $teamId,
            'player_id' => $playerId,
            'player_name_text' => $val,
            'is_captain' => $isCaptain ? 1 : 0,
            'lineup_role' => $role,
            'sort_order' => $i,
        ];
        $id = $this->db->fetchOne("SELECT id FROM scoresheet_lineup 
            WHERE scoresheet_id = :scoresheet_id 
            AND team_id = :team_id 
            AND player_id = :player_id 
            AND player_name_text = :player_name_text
            AND is_captain = :is_captain
            AND lineup_role = :lineup_role 
            AND sort_order = :sort_order", $playerLineUp
        );
        if ($id) return (int)$id;
        
        $this->db->insert('scoresheet_lineup', $playerLineUp);
        return (int)$this->db->lastInsertId();
    }

    private function ensureScoresheetStaffRecord(int $scoresheetId, int $personId, string $roleCode, int $teamId = 1): int
    {
        $staffLineUp = [
            'scoresheet_id' => $scoresheetId,
            'team_id' => $teamId,
            'person_id' => $personId,
            'role' => $roleCode,
        ];
        $id = $this->db->fetchOne("SELECT id FROM scoresheet_staff 
            WHERE scoresheet_id = :scoresheet_id 
            AND team_id = :team_id 
            AND person_id = :person_id 
            AND role = :role", $staffLineUp
        );
        if ($id) return (int)$id;
        
        $this->db->insert('scoresheet_staff', $staffLineUp);
        return (int)$this->db->lastInsertId();
    }

    private function importSubstitutions(int $scoresheetId, string $matchDate, array $row, int $algeriaTeamId, int $otherTeamId): void
    {
        $substitutionCols = [
            'AK', 'AL', 'AM', 'AN', 'AO',
            'AP', 'AQ', 'AR', 'AS', 'AT'
        ];

        $dzTeamCountryId = $this->getCountryByTeamId($algeriaTeamId);
        $otherTeamCountryId = $this->getCountryByTeamId($otherTeamId);

        foreach ($substitutionCols as $colKey) {
            $val = $this->toStr($row[$colKey] ?? null);
            if (!$val) continue;
            [$val, $minute] = $this->parseSubstitutionValue($val);
            $colIn = $this->shiftLetters($colKey, -9);
            $valIn = $this->toStr($row[$colIn] ?? null);
            $playerIdOut = null;
            $playerIdIn = null;
            if ($val) {
                $playerIdOut = $this->ensurePlayerPerson($val, $dzTeamCountryId);
                $personIdOut = $this->getPersonIdByPlayerId($playerIdOut);
                $this->ensurePersonAssignmentRecord($personIdOut, $matchDate);
            }
            if ($valIn) {
                $playerIdIn = $this->ensurePlayerPerson($valIn, $dzTeamCountryId);
            }
            if ($playerIdOut || $playerIdIn) {
                $scoresheetSubstitutionId = $this->ensureScoresheetSubstitutionRecord($scoresheetId, $algeriaTeamId, $playerIdOut, $playerIdIn, $val, $valIn, $minute);
            }
        }

        $advSubstitutionCols = [
            'CS', 'CT', 'CU', 'CV', 'CW',
            'CX', 'CY', 'CZ', 'DA', 'DB'
        ];

        foreach ($advSubstitutionCols as $colKey) {
            $val = $this->toStr($row[$colKey] ?? null);
            if (!$val) continue;
            [$val, $minute] = $this->parseSubstitutionValue($val);
            $colIn = $this->shiftLetters($colKey, -9);
            $valIn = $this->toStr($row[$colIn] ?? null);
            $playerIdOut = null;
            $playerIdIn = null;
            if ($val) {
                $playerIdOut = $this->ensurePlayerPerson($val, $otherTeamCountryId);
                $personIdOut = $this->getPersonIdByPlayerId($playerIdOut);
                $this->ensurePersonAssignmentRecord($personIdOut, $matchDate);
            }
            if ($valIn) {
                $playerIdIn = $this->ensurePlayerPerson($valIn, $otherTeamCountryId);
            }
            if ($playerIdOut || $playerIdIn) {
                $scoresheetSubstitutionId = $this->ensureScoresheetSubstitutionRecord($scoresheetId, $otherTeamId, $playerIdOut, $playerIdIn, $val, $valIn, $minute);
            }
        }
    }

    private function ensureScoresheetSubstitutionRecord($scoresheetId, $teamId, $playerIdOut, $playerIdIn, $val, $valIn, $minute): int
    {
        $subData = [
            'scoresheet_id' => $scoresheetId,
            'team_id' => $teamId,
            'player_out_id' => $playerIdOut,
            'player_in_id' => $playerIdIn,
            'player_out_text' => $val,
            'player_in_text' => $valIn,
            'minute' => $minute,
        ];
        $id = $this->db->fetchOne("SELECT id FROM scoresheet_substitution 
            WHERE scoresheet_id = :scoresheet_id 
            AND team_id = :team_id
            AND player_out_id = :player_out_id 
            AND player_in_id = :player_in_id
            AND player_out_text = :player_out_text
            AND player_in_text = :player_in_text
            AND minute = :minute", $subData
        );
        if ($id) return (int)$id;
        
        $this->db->insert('scoresheet_substitution', $subData);
        return (int)$this->db->lastInsertId();
    }

    private function importGoals(int $fixtureId, ?int $algeriaTeamId, ?int $otherTeamId, string $matchDate,array $row): void
    {
        // Colonnes possibles: but_local, but_visiteur (scores) -> déjà géré via participants
        // Ici on veut but1..butN du type: "Gamouh Rabah 48’" ou "X 12’, Y 45+1’"
        $butsCol = [
            'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
            'BA', 'BB', 'BC', 'BD', 'BE', 'BF',
            'BG', 'BH', 'BI'
        ];
        foreach ($butsCol as $colKey) {
            $val = $this->toStr($row[$colKey] ?? null);
            if (!$val) continue;

            [$player, $minute, $type] = $this->parseButValue($val);
            if (!$player && !$minute) continue;

            $this->ensureGoalRecord($fixtureId, $algeriaTeamId, $player, $matchDate, $minute, $type);
        }
    }

    private function importAdvGoals(int $fixtureId, ?int $algeriaTeamId, ?int $otherTeamId, string $matchDate,array $row): void
    {
        $butsCol = [
            'DD', 'DE', 'DF', 'DG', 'DH', 'DI',
            'DJ', 'DK', 'DL'
        ];
        foreach ($butsCol as $colKey) {
            $val = $this->toStr($row[$colKey] ?? null);
            if (!$val) continue;

            if ($val === 'Non enregistré') {
                $this->ensureUnknownGoalRecord($fixtureId, $val, $otherTeamId);
            } else {
                [$player, $minute, $type] = $this->parseButValue($val);
                if (!$player && !$minute) continue;

                $this->ensureGoalRecord($fixtureId, $otherTeamId, $player, $matchDate, $minute, $type);
            }
        }
    }

    private function ensureGoalRecord(int $fixtureId, ?int $teamId, string $playerName, string $matchDate, ?string $minute, string $type): ?int
    {
        if (!$playerName) {
            return null;
        }

        $countryId = $teamId ? $this->getCountryByTeamId($teamId) : null;
        $playerId = $this->ensurePlayerPerson($playerName, $countryId);
        $personId = $this->getPersonIdByPlayerId($playerId);

        // On enregistre pas l'historique pour les joueurs adverses (own goal).
        if ($type !== 'own_goal') {
            $this->ensurePersonAssignmentRecord($personId, $matchDate);
        }
        $playerData = [
            'fixture_id' => $fixtureId,
            'team_id' => $teamId,
            'scorer_id' => $playerId,
            'minute' => $minute,
            'goal_type' => $type
        ];
        $id = $this->db->fetchOne("SELECT id FROM match_goal 
            WHERE fixture_id = :fixture_id
            AND team_id = :team_id
            AND scorer_id = :scorer_id
            AND minute = :minute
            AND goal_type = :goal_type",
            $playerData
        );
        if ($id) return (int)$id;
        
        $playerData['scorer_text'] = $playerName;
        $this->db->insert('match_goal', $playerData);
        return (int)$this->db->lastInsertId();
    }

    private function ensureUnknownGoalRecord(int $fixtureId, string $playerName, ?int $teamId): ?int
    {
        $playerData = [
            'fixture_id' => $fixtureId,
            'team_id' => $teamId,
            'goal_type' => 'unknown'
        ];
        $id = $this->db->fetchOne("SELECT id FROM match_goal 
            WHERE fixture_id = :fixture_id
            AND team_id = :team_id
            AND goal_type = :goal_type",
            $playerData
        );
        if ($id) return (int)$id;
        
        $playerData['scorer_text'] = $playerName;
        $this->db->insert('match_goal', $playerData);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Parse une valeur de but:
     * - "Antar YAHIA 23'"
     * - "Zidane10'"
     * - "Antar YAHIA 90+2’ (penalty)"
     *
     * @return array{player:string, minute:string, type:'normal'|'penalty'|'own_goal'}
     */
    private function parseButValue(string $butValue): array
    {
        $butValue = trim($butValue);

        // Groupe 1: nom joueur (tout avant la minute)
        // \s* : zéro ou plusieurs espaces (permet Zidane10' ou Zidane 10')
        // Groupe 2: minute (ex: 23, 90+2)
        // ['’]    : supporte les deux types d'apostrophes
        // Groupe 3: type optionnel (penalty|own_goal)
        $pattern = "/^(.+?)\s*(\d+(?:\+\d+)?)['’]\s*(?:\(([^)]+)\))?$/u";

        if (!preg_match($pattern, $butValue, $m)) {
            dd("Format de but invalide: {$butValue}");
        }

        $player = trim($m[1]);
        $minute = $m[2];

        $typeRaw = isset($m[3]) ? trim($m[3]) : '';
        $type = $typeRaw === '' ? 'normal' : $typeRaw;

        $allowed = ['normal', 'penalty', 'own_goal'];
        if (!in_array($type, $allowed, true)) {
            dd("Type de but inconnu: {$type} (attendu: normal|penalty|own_goal)");
        }

        return [
            $player,
            $minute,
            $type,
        ];
    }

    /**
     * Parse une valeur de remplaçant :
     * - "Belbekri"
     * - "Belbekri 62'"
     * - "Belbekri62'"
     * - "Belbekri 90+2’"
     * - "Abdellaoui  Ayoub 83"
     * - "Abdellaoui Ayoub 83"  // espace insécable
     *
     * @return array{0:string, 1:?string} [player, minute|null]
     */
    private function parseSubstitutionValue(string $value): array
    {
        // 1) Trim + normalisation des espaces (y compris insécables)
        $value = trim($value);
        $value = preg_replace('/[\h\x{00A0}]+/u', ' ', $value); // 1 seul espace partout

        if ($value === '') {
            dd('Remplaçant vide');
        }

        /**
         * Cas avec minute en fin de chaîne :
         * - "Belbekri 62'"
         * - "Belbekri62'"
         * - "Belbekri 90+2’"
         * - "Abdellaoui Ayoub 83"
         * - "AbdellaouiAyoub83" (si jamais)
         *
         * Groupe 1 = nom
         * Groupe 2 = minute (23, 90+2, etc.)
         * Apostrophe finale optionnelle
         */
        $withMinute = "/^(.+?)\s*(\d+(?:\+\d+)?)\s*['’]?$/u";

        if (preg_match($withMinute, $value, $m)) {
            $player = trim($m[1]);
            $player = preg_replace('/[\h\x{00A0}]+/u', ' ', $player); // sécurité
            $minute = $m[2];

            if ($player === '') {
                dd("Remplaçant invalide (nom vide): {$value}");
            }

            return [$player, $minute];
        }

        // Cas sans minute
        return [$value, null];
    }

    private function splitEvents(string $s): array
    {
        // séparation par ; , \n
        $parts = preg_split('/[;\n,]+/u', $s) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') $out[] = $p;
        }
        return $out;
    }

    private function normalizeMinute(string $s): ?string
    {
        $s = trim($s);
        $s = str_replace(['’',"'"], '', $s);
        $s = preg_replace('/\s+/', '', $s);
        return $s === '' ? null : $s;
    }

    private function extractNameAndMinute(string $s): array
    {
        // "Gamouh Rabah 48’" ; "X 90+2’"
        $x = trim($s);
        $x = str_replace(['’'], ["'"], $x);
        if (preg_match("/^(.*?)(\d{1,3}(?:\+\d{1,2})?)'?$/u", $x, $m)) {
            $name = trim($m[1]);
            $min = trim($m[2]);
            return [$name !== '' ? $name : null, $min !== '' ? $min : null];
        }
        return [$x !== '' ? $x : null, null];
    }

    // ----------------- Ensures (upserts) -----------------

    private function ensureCategory(string $name, string $gender): int
    {
        $id = $this->db->fetchOne("SELECT id FROM category WHERE name = :n", ['n'=>$name]);
        if ($id) return (int)$id;
        $this->db->insert('category', ['name'=>$name,'gender'=>$gender]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureCompetitions(string $name, int $categoryId): array
    {
        $parts = explode('|', $name);
        $ids = [];
        foreach ($parts as $name) {
            $name = trim($name);
            $id = $this->db->fetchOne("SELECT id FROM competition WHERE name = :n AND category_id = :c", ['n' => $name, 'c' => $categoryId]);
            if ($id) {
                $ids[] = (int)$id;
            } else {
                $this->db->insert('competition', ['name' => $name, 'category_id' => $categoryId]);
                $ids[] = (int)$this->db->lastInsertId();
            }
        }
        return $ids;
    }

    /**
     * Best-effort mapping from competition name to type (league/cup/friendly/qualifier) + organizer (FAF/CAF/FIFA/UEFA/etc)
     */
    private function ensureCompetitionTypeAndOrganizer(string $competitionName, string $matchType): array
    {
        // Best-effort mapping
        $n = $this->norm($competitionName);

        if (str_contains($n, 'Amical')) {
            return ['friendly', 'FAF'];
        }
        if (str_contains($n, 'CAN')) {
            if ($matchType === 'Eliminatoire') {
                return ['qualification', 'CAF'];
            }
            return ['international', 'CAF'];
        }
        if (str_contains($n, 'match amical') || str_contains($n, 'friendly')) {
            return ['friendly', 'FAF'];
        }
        if (str_contains($n, 'qualification')) {
            return ['qualification', 'FAF'];
        }
        // défaut
        return ['friendly', 'FAF'];
    }

    private function ensureSeasonFromDateOrYear(?string $ymd, ?int $year): int
    {
        $y = $year;
        if ($y === null && $ymd) $y = (int)substr($ymd,0,4);
        if ($y === null) $y = 0;
        $name = (string)$y;

        $id = $this->db->fetchOne("SELECT id FROM season WHERE name = :n", ['n'=>$name]);
        if ($id) return (int)$id;

        $this->db->insert('season', ['name'=>$name,'year_start'=>$y ?: null,'year_end'=>$y ?: null]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureSeason(string $year): int
    {
        $id = $this->db->fetchOne("SELECT id FROM season WHERE name = :n", ['n' => $year]);
        if ($id) return (int)$id;

        $this->db->insert('season', ['name' => $year, 'year_start' => $year, 'year_end' => $year]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureEditions(array $competitionIds, int $seasonId, ?string $names): array
    {
        $ids = [];
        if (!$names) {
            return $ids;
        }
        $parts = explode('|', $names);
        $i = 0;
        foreach ($parts as $name) {
            $ids[] = $this->ensureEditionSingle($competitionIds[$i], $seasonId, trim($name));
            $i++;
        }
        return $ids;
    }

    private function ensureEditionSingle(int $competitionId, int $seasonId, string $name): int
    {
        $id = $this->db->fetchOne(
            "SELECT id FROM edition WHERE competition_id = :c AND season_id = :s AND name = :n",
            ['c' => $competitionId, 's' => $seasonId, 'n' => $name]
        );
        if ($id) return (int)$id;

        $this->db->insert('edition', [
            'competition_id' => $competitionId,
            'season_id' => $seasonId,
            'name' => $name
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureStages(array $editionIds, ?string $name): array
    {
        $ids = [];
        if (!$name) return $ids;
        foreach ($editionIds as $editionId) {
            $ids[] = $this->ensureStage($editionId, $name);
        }

        return $ids;
    }

    private function ensureStage(int $editionId, string $name): int
    {
        $id = $this->db->fetchOne("SELECT id FROM stage WHERE edition_id = :e AND name = :n", ['e' => $editionId, 'n' => $name]);
        if ($id) return (int)$id;

        $this->db->insert('stage', [
            'edition_id' => $editionId,
            'name' => $name,
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureCountry(string $name): int
    {
        $id = $this->db->fetchOne("SELECT id FROM country WHERE name = :n", ['n'=>$name]);
        if ($id) return (int)$id;
        $this->db->insert('country', ['name'=>$name,'iso2'=>null,'iso3'=>null,'fifa_code'=>null]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureCity(?string $city, ?string $country): ?int
    {
        $city = $city ? trim($city) : null;
        if (!$city) return null;
        $countryId = $country ? $this->ensureCountry($country) : null;

        $id = $this->db->fetchOne(
            "SELECT id FROM city WHERE name=:n AND ((:c IS NULL AND country_id IS NULL) OR country_id=:c)",
            ['n'=>$city,'c'=>$countryId]
        );
        if ($id) return (int)$id;

        $this->db->insert('city', ['name'=>$city,'country_id'=>$countryId,'region_id'=>null]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureStadium(string $stadium, ?int $cityId, ?int $countryId): int
    {
        $id = $this->db->fetchOne(
            "SELECT id FROM stadium WHERE name=:n",
            ['n'=>$stadium]
        );
        if ($id) return (int)$id;

        $this->db->insert('stadium', [
            'name'=>$stadium,
            'city_id'=>$cityId,
            'country_id'=>$countryId,
            'capacity'=>null
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureNationalTeamAsTeam(string $countryName, int $categoryId): int
    {
        $countryId = $this->ensureCountry($countryName);

        $ntId = $this->db->fetchOne(
            "SELECT id FROM national_team WHERE country_id=:c AND category_id=:cat",
            ['c'=>$countryId,'cat'=>$categoryId]
        );
        if (!$ntId) {
            $this->db->insert('national_team', [
                'country_id' => $countryId,
                'category_id' => $categoryId,
                'name' => $countryName
            ]);
            $ntId = (int)$this->db->lastInsertId();
        } else {
            $ntId = (int)$ntId;
        }

        $teamId = $this->db->fetchOne(
            "SELECT id FROM team WHERE team_type='NATIONAL' AND national_team_id=:nt",
            ['nt' => $ntId]
        );
        if ($teamId) return (int)$teamId;

        $this->db->insert('team', [
            'team_type'=>'NATIONAL',
            'club_id'=>null,
            'national_team_id'=>$ntId,
            'display_name'=>$countryName
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function upsertFixture(
        OutputInterface $output,
        int $matchNo,
        int $seasonId,
        int $categoryId,
        ?string $matchDate,
        ?int $stadiumId,
        ?int $cityId,
        ?int $countryId,
        int $played,
        int $isOfficial,
        ?string $notes,
        ?string $internalNotes,
        array $competitionIds = [],
        array $editionIds = [],
        array $stageIds = [],
    ): int {
        // 1) Best-effort: matchNo + category + date
        $existing = $this->db->fetchOne(
            "SELECT id
            FROM fixture
            WHERE external_match_no = :n
            AND category_id = :cat
            AND ((:d IS NULL AND match_date IS NULL) OR match_date = :d)
            LIMIT 1",
            ['n' => $matchNo, 'cat' => $categoryId, 'd' => $matchDate]
        );

        $fixtureData = [
            'external_match_no' => $matchNo,
            'season_id'         => $seasonId,
            'matchday_id'       => null,
            'division_id'       => null,
            'category_id'       => $categoryId,
            'match_date'        => $matchDate,
            'stadium_id'        => $stadiumId,
            'city_id'           => $cityId,
            'country_id'        => $countryId,
            'played'            => $played,
            'is_official'       => $isOfficial,
            'notes'             => $notes,
            'internal_notes'    => $internalNotes,
        ];

        if ($existing) {
            $this->db->update('fixture', $fixtureData, ['id' => (int)$existing]);
            $fixtureId = (int)$existing;
        } else {
            $output->writeln(sprintf(
                '<info>Insertion fixture (archifoot.fixture): %s</info>',
                json_encode($fixtureData, JSON_UNESCAPED_UNICODE)
            ));
            $this->db->insert('fixture', $fixtureData);
            $fixtureId = (int)$this->db->lastInsertId();
        }

        // 2) Upsert relations (idempotent)
        $this->syncFixtureCompetitions($fixtureId, $competitionIds);
        $this->syncFixtureEditions($fixtureId, $editionIds);

        // Stage => déduit Edition + Competition (cohérence forte)
        $this->syncFixtureStagesWithConsistency($fixtureId, $stageIds);

        return $fixtureId;
    }

    private function syncFixtureCompetitions(int $fixtureId, array $competitionIds): void
    {
        $competitionIds = array_values(array_unique(array_filter($competitionIds, 'is_int')));

        foreach ($competitionIds as $cid) {
            $this->db->executeStatement(
                "INSERT IGNORE INTO fixture_competition (fixture_id, competition_id)
                VALUES (:f, :c)",
                ['f' => $fixtureId, 'c' => $cid]
            );
        }
    }

    private function syncFixtureEditions(int $fixtureId, array $editionIds): void
    {
        $editionIds = array_values(array_unique(array_filter($editionIds, 'is_int')));

        foreach ($editionIds as $eid) {
            $this->db->executeStatement(
                "INSERT IGNORE INTO fixture_edition (fixture_id, edition_id)
                VALUES (:f, :e)",
                ['f' => $fixtureId, 'e' => $eid]
            );
        }
    }

    private function syncFixtureStagesWithConsistency(int $fixtureId, array $stageIds): void
    {
        $stageIds = array_values(array_unique(array_filter($stageIds, 'is_int')));
        if (!$stageIds) {
            return;
        }

        // 1) insérer les stages (idempotent)
        foreach ($stageIds as $sid) {
            $this->db->executeStatement(
                "INSERT IGNORE INTO fixture_stage (fixture_id, stage_id)
                VALUES (:f, :s)",
                ['f' => $fixtureId, 's' => $sid]
            );
        }

        // 2) déduire éditions et compétitions à partir des stages
        // On récupère (stage_id -> edition_id -> competition_id)
        $rows = $this->db->fetchAllAssociative(
            "SELECT s.id AS stage_id, e.id AS edition_id, e.competition_id
            FROM stage s
            JOIN edition e ON e.id = s.edition_id
            WHERE s.id IN (" . implode(',', array_map('intval', $stageIds)) . ")"
        );

        $editionIds = [];
        $competitionIds = [];

        foreach ($rows as $r) {
            if (!empty($r['edition_id'])) {
                $editionIds[] = (int)$r['edition_id'];
            }
            if (!empty($r['competition_id'])) {
                $competitionIds[] = (int)$r['competition_id'];
            }
        }

        // 3) upsert éditions + compétitions
        $this->syncFixtureEditions($fixtureId, $editionIds);
        $this->syncFixtureCompetitions($fixtureId, $competitionIds);
    }


    private function upsertFixtureParticipant(int $fixtureId, int $teamId, string $role, ?int $score, ?string $venueRole): void
    {
        $id = $this->db->fetchOne(
            "SELECT id FROM fixture_participant WHERE fixture_id=:f AND role=:r",
            ['f'=>$fixtureId,'r'=>$role]
        );

        $data = [
            'fixture_id'=>$fixtureId,
            'team_id'=>$teamId,
            'role'=>$role,
            'score'=>$score,
            'score_extra'=>null,
            'score_penalty'=>null,
            'outcome'=>null,
            'venue_role'=>$venueRole,
        ];

        if ($id) {
            $this->db->update('fixture_participant', $data, ['id'=>(int)$id]);
        } else {
            $this->db->insert('fixture_participant', $data);
        }
    }

    private function ensureScoresheet(int $fixtureId, array $vals): int
    {
        $id = $this->db->fetchOne("SELECT id FROM scoresheet WHERE fixture_id=:f", ['f' => $fixtureId]);
        if ($id) {
            $this->db->update('scoresheet', $vals, ['id'=>(int)$id]);
            return (int)$id;
        }
        $vals['fixture_id'] = $fixtureId;
        $this->db->insert('scoresheet', $vals);
        return (int)$this->db->lastInsertId();
    }

    private function ensureCoachPerson(string $fullName, string $role, int $nationalityId = 1): ?int
    {
        $fullName = trim($fullName);
        $personId = $this->ensurePerson($fullName, $nationalityId);

        if ($personId) {
        $coachId = $this->db->fetchOne(
            "SELECT id FROM coach WHERE person_id = :p",
                ['p' => $personId]
        );
        if (!$coachId) {
            $this->db->insert('coach', [
                    'person_id' => $personId,
                    'role' => $role,
            ]);
                $coachId = (int)$this->db->lastInsertId();
        }

            return $coachId;
        }

        return null;
    }

    private function ensurePlayerPerson(string $fullName, int $nationalityId = 1): ?int
    {
        $fullName = trim($fullName);
        $personId = $this->ensurePerson($fullName, $nationalityId);

        if ($personId) {
            $playerId = $this->db->fetchOne(
                "SELECT id FROM player WHERE person_id = :p",
                ['p' => $personId]
            );
            if (!$playerId) {
                $this->db->insert('player', [
                    'person_id' => $personId,
                ]);
                $playerId = (int)$this->db->lastInsertId();
            }

            return $playerId;
        }

        return null;
    }

    private function ensurePerson(string $fullName, ?int $nationalityId = null): ?int
    {
        if (!$fullName) {
            return null;
        }
        $fullName = trim($fullName);
        $fullName = str_replace('  ', ' ', $fullName);
        $fullName = str_replace('   ', ' ', $fullName);
        $personId = $this->db->fetchOne(
            "SELECT id FROM person WHERE full_name = :n",
            ['n' => $fullName]
        );
        if (!$personId) {
            $data = [
                'full_name' => $fullName,
            ];
            if ($nationalityId) {
                $data['nationality_country_id'] = $nationalityId;
            }
            $this->db->insert('person', $data);
            $personId = (int)$this->db->lastInsertId();
        } else {
            $personId = (int)$personId;
        }

        return $personId;
    }

    /**
     * Décale une chaîne A..Z (type colonnes Excel) de $shift positions.
     * Ex: shiftLetters('AK', -9) => 'AB'
     *     shiftLetters('A', -1)  => null (hors borne)
     *
     * Règle : A=1 ... Z=26, AA=27, etc.
     */
    function shiftLetters(string $s, int $shift): ?string
    {
        $s = strtoupper(trim($s));
        if ($s === '' || !preg_match('/^[A-Z]+$/', $s)) {
            throw new InvalidArgumentException("Entrée invalide: '$s' (A..Z uniquement).");
        }

        // 1) Convertir en nombre (base-26 Excel: A=1..Z=26)
        $n = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $digit = ord($s[$i]) - ord('A') + 1; // 1..26
            $n = $n * 26 + $digit;
        }

        // 2) Appliquer le shift
        $n += $shift;

        // 3) Hors borne (pas de 0 ou négatif)
        if ($n <= 0) {
            return null;
        }

        // 4) Reconvertir en lettres (inverse Excel)
        $out = '';
        while ($n > 0) {
            $n--; // clé: passer en 0..25
            $rem = $n % 26;
            $out = chr(ord('A') + $rem) . $out;
            $n = intdiv($n, 26);
        }

        return $out;
    }

    private function importReferees(int $scoresheetId, string $matchDate, array $row): void
    {
        $mainReferee = $this->toStr($row['BQ'] ?? null);
        if ($mainReferee) {
            $countryName = $this->toStr($row['BR'] ?? null);
            $countryId = $countryName ? $this->ensureCountry($countryName) : null;
            $refereeId = $this->ensureRefereePerson($mainReferee, $countryId);
            $personId = $this->getPersonIdByRefereeId($refereeId);
            if ($refereeId) {
                $this->ensureScoresheetOfficialRecord($scoresheetId, $personId, 'MAIN_REFEREE', $mainReferee);
            }
        }

        $assistant1Referee = $this->toStr($row['BS'] ?? null);
        if ($assistant1Referee) {
            $countryName = $this->toStr($row['BT'] ?? null);
            $countryId = $countryName ? $this->ensureCountry($countryName) : null;
            $refereeId = $this->ensureRefereePerson($assistant1Referee, $countryId);
            $personId = $this->getPersonIdByRefereeId($refereeId);
            if ($refereeId) {
                $this->ensureScoresheetOfficialRecord($scoresheetId, $personId, 'ASSISTANT_REFEREE', $assistant1Referee);
            }
        }

        $assistant2Referee = $this->toStr($row['BU'] ?? null);
        if ($assistant2Referee) {
            $countryName = $this->toStr($row['BV'] ?? null);
            $countryId = $countryName ? $this->ensureCountry($countryName) : null;
            $refereeId = $this->ensureRefereePerson($assistant2Referee, $countryId);
            $personId = $this->getPersonIdByRefereeId($refereeId);
            if ($refereeId) {
                $this->ensureScoresheetOfficialRecord($scoresheetId, $personId, 'ASSISTANT_REFEREE', $assistant2Referee);
            }
        }

        $fourthReferee = $this->toStr($row['BW'] ?? null);
        if ($fourthReferee) {
            $countryName = $this->toStr($row['BX'] ?? null);
            $countryId = $countryName ? $this->ensureCountry($countryName) : null;
            $refereeId = $this->ensureRefereePerson($fourthReferee, $countryId);
            $personId = $this->getPersonIdByRefereeId($refereeId);
            if ($refereeId) {
                $this->ensureScoresheetOfficialRecord($scoresheetId, $personId, 'FOURTH_OFFICIAL', $fourthReferee);
            }
        }
    }

    private function ensureScoresheetOfficialRecord(int $scoresheetId, int $personId, string $roleCode, string $name): int
    {
        $officialReferee = [
            'scoresheet_id' => $scoresheetId,
            'role' => $roleCode,
            'person_id' => $personId,
            'name_text' => $name,
        ];
        $id = $this->db->fetchOne("SELECT id FROM scoresheet_official 
            WHERE scoresheet_id = :scoresheet_id
            AND role = :role
            AND person_id = :person_id 
            AND name_text = :name_text", $officialReferee
        );
        if ($id) return (int)$id;
        
        $this->db->insert('scoresheet_official', $officialReferee);
        return (int)$this->db->lastInsertId();
    }

    private function ensureRefereePerson(string $fullName, int $nationalityId = null): ?int
    {
        $fullName = trim($fullName);
        $personId = $this->ensurePerson($fullName, $nationalityId);

        if ($personId) {
            $refereeId = $this->db->fetchOne(
                "SELECT id FROM referee WHERE person_id = :p",
                    ['p' => $personId]
            );
            if (!$refereeId) {
                $this->db->insert('referee', [
                        'person_id' => $personId,
                        'level' => 'International' // par défaut
                ]);
                $refereeId = (int)$this->db->lastInsertId();
            }

            return $refereeId;
        }

        return null;
    }

    private function getCountryByTeamId(int $teamId): ?int
    {
        return $this->db->fetchOne("SELECT country_id FROM national_team
        LEFT JOIN team ON national_team.id = team.national_team_id
        WHERE team.id = :team_id", ['team_id' => $teamId]);
    }

    private function getPersonIdByPlayerId(int $playerId): ?int
    {
        return $this->db->fetchOne("SELECT person_id FROM player WHERE id = :pid", ['pid' => $playerId]);
    }

    private function getPersonIdByCoachId(int $coachId): ?int
    {
        return $this->db->fetchOne("SELECT person_id FROM coach WHERE id = :cid", ['cid' => $coachId]);
    }

    private function getPersonIdByRefereeId(int $refereeId): ?int
    {
        return $this->db->fetchOne("SELECT person_id FROM referee WHERE id = :rid", ['rid' => $refereeId]);
    }
}
