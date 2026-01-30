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
    public function __construct(private readonly Connection $db) { parent::__construct(); }

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

        // Constantes (EN seniors)
        $categoryId = $this->ensureCategory('Seniors', 'M', null, null);
        $competitionId = $this->ensureCompetition("Équipe nationale - Matchs", 'friendly', 'FAF', $categoryId);

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

                $dateVal = $this->get($row, $headers, ['date']);
                $matchDate = $this->parseDateToYmd($dateVal); // YYYY-MM-DD ou null
                
                $teamAName = $this->toStr($this->get($row, $headers, ['pays a','equipe a','équipe a']));
                $teamBName = $this->toStr($this->get($row, $headers, ['pays b','equipe b','équipe b']));
                if (!$teamAName || !$teamBName) {
                    $skipped++;
                    continue;
                }

                $scoreA = $this->toInt($this->get($row, $headers, ['buts a','but a','score a']));
                $scoreB = $this->toInt($this->get($row, $headers, ['buts b','but b','score b']));

                $isOfficialTxt = $this->toStr($this->get($row, $headers, ['matches officiels','match officiel','officiel']));
                $isOfficial = $this->toBool01($isOfficialTxt);

                $played = 1; // par défaut
                $playedTxt = $this->toStr($this->get($row, $headers, ['status played','joué','played']));
                if ($playedTxt !== null) $played = $this->toBool01($playedTxt);

                // Venue
                $stadiumName = $this->toStr($this->get($row, $headers, ['stade','stade ']));
                $cityName = $this->toStr($this->get($row, $headers, ['ville -stade','ville','ville stade']));
                $venueCountryName = $this->toStr($this->get($row, $headers, ['pays -stade','pays stade','pays']));
                $stadiumId = $stadiumName ? $this->ensureStadium($stadiumName, $cityName, $venueCountryName) : null;

                $seasonId = $this->ensureSeasonFromDateOrYear($matchDate, $this->toInt($this->get($row, $headers, ['année','annee'])));
                $editionId = $this->ensureEdition($competitionId, $seasonId, (string)$seasonId, null);
                $stageId = $this->ensureStage($editionId, 'Match', 'round', 0, null);

                $fixtureId = $this->upsertFixture(
                    $output,
                    $matchNo,
                    $competitionId,
                    $seasonId,
                    $editionId,
                    $stageId,
                    $categoryId,
                    $matchDate,
                    $stadiumId,
                    $played,
                    $isOfficial,
                    $this->toStr($this->get($row, $headers, ['bulles','notes','observation']))
                );

                // Teams A/B (national teams via country)
                $teamAId = $this->ensureNationalTeamAsTeam($teamAName, $categoryId);
                $teamBId = $this->ensureNationalTeamAsTeam($teamBName, $categoryId);

                $this->upsertFixtureParticipant($fixtureId, $teamAId, 'A', $scoreA, null);
                $this->upsertFixtureParticipant($fixtureId, $teamBId, 'B', $scoreB, null);

                // Scoresheet (créé même si vide -> utile pour enrichissement)
                $coach = $this->toStr($this->get($row, $headers, ['entraineur', 'entraîneur']));
                $report = $this->toStr($this->get($row, $headers, ['rapport','report']));
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
                    'report' => $this->mergeNotes($report, $coach ? "Entraineur: {$coach}" : null),
                    'signed_place' => $this->toStr($this->get($row, $headers, ['fait_a','fait à','fait a'])),
                    'signed_on' => $this->parseDateToYmd($this->get($row, $headers, ['le'])),
                    'form_state' => '0',
                ]);

                // === Lineups (j1..j18 etc) ===
                $algeriaTeamId = $this->resolveAlgeriaTeamId($teamAName, $teamBName, $teamAId, $teamBId);
                $this->importLineups($scoresheetId, $teamAId, $teamBId, $algeriaTeamId, $headers, $row);

                // === Substitutions (chang1..5 etc) ===
                $this->importSubstitutions($scoresheetId, $teamAId, $teamBId, $algeriaTeamId, $headers, $row);

                // === Goals (but1..butN, but_local/but_visiteur) ===
                $this->importGoals($fixtureId, $teamAId, $teamBId, $algeriaTeamId, $headers, $row);

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

    private function importLineups(int $scoresheetId, int $teamAId, int $teamBId, ?int $algeriaTeamId, array $headers, array $row): void
    {
        // détecte colonnes du style: id_joueur_local_1, id_joueur_visiteur_1, joueur a 1, etc.
        foreach ($headers as $h => $col) {
            if (!preg_match('/^(id_)?joueur_(local|visiteur)_(\d+)$/', $h, $m)) continue;
            $side = ($m[2] === 'local') ? 'A' : 'B';
            $teamId = ($side === 'A') ? $teamAId : $teamBId;

            $name = $this->toStr($row[$col] ?? null);
            if (!$name) continue;

            // Titulaire / remplaçant: on considère 1..11 = STARTER, >11 = SUB (ajuste si besoin)
            $idx = (int)$m[3];
            $role = ($idx <= 11) ? 'STARTER' : 'SUB';

            // Insert simple (fallback text)
            $this->db->executeStatement(
                "INSERT INTO scoresheet_lineup (scoresheet_id, team_id, player_id, player_name_text, shirt_number, lineup_role, is_captain, position_id, sort_order)
                 VALUES (:sid,:tid,NULL,:name,NULL,:role,0,NULL,:ord)",
                ['sid'=>$scoresheetId,'tid'=>$teamId,'name'=>$name,'role'=>$role,'ord'=>$idx]
            );
        }

        if ($algeriaTeamId === null) return;

        foreach ($headers as $h => $col) {
            if (!preg_match('/^j(\d+)$/', $h, $m)) continue;
            $name = $this->toStr($row[$col] ?? null);
            if (!$name) continue;

            $idx = (int)$m[1];
            $role = ($idx <= 11) ? 'STARTER' : 'SUB';

            $this->db->executeStatement(
                "INSERT INTO scoresheet_lineup (scoresheet_id, team_id, player_id, player_name_text, shirt_number, lineup_role, is_captain, position_id, sort_order)
                 VALUES (:sid,:tid,NULL,:name,NULL,:role,0,NULL,:ord)",
                ['sid'=>$scoresheetId,'tid'=>$algeriaTeamId,'name'=>$name,'role'=>$role,'ord'=>$idx]
            );
        }
    }

    private function importSubstitutions(int $scoresheetId, int $teamAId, int $teamBId, ?int $algeriaTeamId, array $headers, array $row): void
    {
        // Format legacy: chang1_local / chang1_par_local / chang1_minute_local
        for ($n=1; $n<=5; $n++) {
            foreach (['local'=>'A','visiteur'=>'B'] as $k => $side) {
                $out = $this->toStr($this->get($row,$headers,[ "chang{$n}_$k", "chang{$n} $k" ]));
                $inn = $this->toStr($this->get($row,$headers,[ "chang{$n}_par_$k", "chang{$n}_par_$k" ]));
                $min = $this->toStr($this->get($row,$headers,[ "chang{$n}_minute_$k", "chang{$n}_minute_$k" ]));

                if (!$out && !$inn) continue;

                $teamId = ($side==='A') ? $teamAId : $teamBId;

                // minute peut être vide; sinon normaliser (ex: "48’" -> "48")
                $minute = $min ? $this->normalizeMinute($min) : null;

                $this->db->executeStatement(
                    "INSERT INTO scoresheet_substitution (scoresheet_id, team_id, player_out_id, player_in_id, player_out_text, player_in_text, minute)
                     VALUES (:sid,:tid,NULL,NULL,:out,:in,:min)",
                    ['sid'=>$scoresheetId,'tid'=>$teamId,'out'=>$out,'in'=>$inn,'min'=>$minute]
                );
            }
        }

        if ($algeriaTeamId === null) return;

        foreach ($headers as $h => $col) {
            if (!preg_match('/^chang(\d+)$/', $h, $m)) continue;
            $val = $this->toStr($row[$col] ?? null);
            if (!$val) continue;

            [$name, $minute] = $this->extractNameAndMinute($val);
            if (!$name && !$minute) continue;

            $this->db->executeStatement(
                "INSERT INTO scoresheet_substitution (scoresheet_id, team_id, player_out_id, player_in_id, player_out_text, player_in_text, minute)
                 VALUES (:sid,:tid,NULL,NULL,:out,NULL,:min)",
                ['sid'=>$scoresheetId,'tid'=>$algeriaTeamId,'out'=>$name,'min'=>$minute]
            );
        }
    }

    private function importGoals(int $fixtureId, int $teamAId, int $teamBId, ?int $algeriaTeamId, array $headers, array $row): void
    {
        // Colonnes possibles: but_local, but_visiteur (scores) -> déjà géré via participants
        // Ici on veut but1..butN du type: "Gamouh Rabah 48’" ou "X 12’, Y 45+1’"
        foreach ($headers as $h => $col) {
            if (!preg_match('/^but(\d+)?_(local|visiteur)$/', $h, $m) && !preg_match('/^but(\d+)?\s*(local|visiteur)$/', $h, $m)) {
                // ex: but1_local / but2_visiteur
                if (!preg_match('/^but(\d+)?_(a|b)$/', $h) && !preg_match('/^but(\d+)$/', $h)) continue;
            }

            $val = $this->toStr($row[$col] ?? null);
            if (!$val) continue;

            $teamId = null;
            if (str_contains($h,'visiteur') || str_ends_with($h,'_b')) {
                $teamId = $teamBId;
            } elseif (str_contains($h,'local') || str_ends_with($h,'_a')) {
                $teamId = $teamAId;
            } elseif ($algeriaTeamId !== null) {
                $teamId = $algeriaTeamId;
            } else {
                continue;
            }

            foreach ($this->splitEvents($val) as $evt) {
                [$name, $minute] = $this->extractNameAndMinute($evt);
                if (!$name && !$minute) continue;

                $this->db->executeStatement(
                    "INSERT INTO match_goal (fixture_id, team_id, scorer_id, scorer_text, minute, goal_type)
                     VALUES (:fid,:tid,NULL,:name,:min,'normal')",
                    ['fid'=>$fixtureId,'tid'=>$teamId,'name'=>$name,'min'=>$minute]
                );
            }
        }
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

    private function ensureCategory(string $name, string $gender, ?int $ageMin, ?int $ageMax): int
    {
        $id = $this->db->fetchOne("SELECT id FROM category WHERE name = :n", ['n'=>$name]);
        if ($id) return (int)$id;
        $this->db->insert('category', ['name'=>$name,'gender'=>$gender,'age_min'=>$ageMin,'age_max'=>$ageMax]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureCompetition(string $name, string $type, string $organizer, int $categoryId): int
    {
        $id = $this->db->fetchOne("SELECT id FROM competition WHERE name = :n AND category_id = :c", ['n'=>$name,'c'=>$categoryId]);
        if ($id) return (int)$id;
        $this->db->insert('competition', ['name'=>$name,'type'=>$type,'organizer'=>$organizer,'category_id'=>$categoryId]);
        return (int)$this->db->lastInsertId();
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

    private function ensureEdition(int $competitionId, int $seasonId, string $name, ?int $divisionId): int
    {
        $id = $this->db->fetchOne(
            "SELECT id FROM edition WHERE competition_id=:c AND season_id=:s AND name=:n",
            ['c'=>$competitionId,'s'=>$seasonId,'n'=>$name]
        );
        if ($id) return (int)$id;

        $this->db->insert('edition', [
            'competition_id'=>$competitionId,
            'season_id'=>$seasonId,
            'name'=>$name,
            'division_id'=>$divisionId
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function ensureStage(int $editionId, string $name, string $type, int $isFinal, ?int $sort): int
    {
        $id = $this->db->fetchOne("SELECT id FROM stage WHERE edition_id=:e AND name=:n", ['e'=>$editionId,'n'=>$name]);
        if ($id) return (int)$id;

        $this->db->insert('stage', [
            'edition_id'=>$editionId,
            'name'=>$name,
            'stage_type'=>$type,
            'is_final'=>$isFinal,
            'sort_order'=>$sort
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

    private function ensureStadium(string $stadium, ?string $city, ?string $country): int
    {
        $countryId = $country ? $this->ensureCountry($country) : null;
        $cityId = $this->ensureCity($city, $country);

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
                'country_id'=>$countryId,
                'category_id'=>$categoryId,
                'name'=>$countryName . " (NT)"
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
        int $competitionId,
        int $seasonId,
        int $editionId,
        int $stageId,
        int $categoryId,
        ?string $matchDate,
        ?int $stadiumId,
        int $played,
        int $isOfficial,
        ?string $notes
    ): int {
        // unique logique: (external_match_no, competition_id, match_date, A, B) n'est pas dispo ici => on fait best-effort par matchNo+competition+date
        $existing = $this->db->fetchOne(
            "SELECT id FROM fixture WHERE external_match_no=:n AND competition_id=:c AND ((:d IS NULL AND match_date IS NULL) OR match_date=:d) LIMIT 1",
            ['n'=>$matchNo,'c'=>$competitionId,'d'=>$matchDate]
        );
        if ($existing) {
            $this->db->update('fixture', [
                'season_id'=>$seasonId,
                'edition_id'=>$editionId,
                'stage_id'=>$stageId,
                'matchday_id'=>null,
                'division_id'=>null,
                'category_id'=>$categoryId,
                'match_date'=>$matchDate,
                'stadium_id'=>$stadiumId,
                'played'=>$played,
                'is_official'=>$isOfficial,
                'notes'=>$notes,
            ], ['id'=>(int)$existing]);
            return (int)$existing;
        }

        $fixtureData = [
            'external_match_no'=>$matchNo,
            'competition_id'=>$competitionId,
            'season_id'=>$seasonId,
            'edition_id'=>$editionId,
            'stage_id'=>$stageId,
            'matchday_id'=>null,
            'division_id'=>null,
            'category_id'=>$categoryId,
            'match_date'=>$matchDate,
            'stadium_id'=>$stadiumId,
            'city_id'=>null,
            'country_id'=>null,
            'played'=>$played,
            'is_official'=>$isOfficial,
            'notes'=>$notes,
        ];

        $output->writeln(sprintf(
            '<info>Insertion fixture (archifoot.fixture): %s</info>',
            json_encode($fixtureData, JSON_UNESCAPED_UNICODE)
        ));

        $this->db->insert('fixture', $fixtureData);
        return (int)$this->db->lastInsertId();
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
            'is_winner'=>null,
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
        $id = $this->db->fetchOne("SELECT id FROM scoresheet WHERE fixture_id=:f", ['f'=>$fixtureId]);
        if ($id) {
            $this->db->update('scoresheet', $vals, ['id'=>(int)$id]);
            return (int)$id;
        }
        $vals['fixture_id'] = $fixtureId;
        $this->db->insert('scoresheet', $vals);
        return (int)$this->db->lastInsertId();
    }
}
