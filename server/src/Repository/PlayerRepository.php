<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Service\DateFormatter;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    private const ALGERIA_COUNTRY_ID = 1;

    public function __construct(
        ManagerRegistry $registry,
        private readonly DateFormatter $dateFormatter
    ) {
        parent::__construct($registry, Player::class);
    }

    /**
     * @return array{items: array<int, array{id:int,fullName:string,photoUrl:?string}>, total:int}
     */
    public function findAlgeriaSeniorPlayers(string $query, int $page, int $perPage): array
    {
        $baseQb = $this->createAlgeriaSeniorPlayersQueryBuilder()
            ->select('DISTINCT p.id AS id, person.fullName AS fullName, person.photoUrl AS photoUrl');

        if ($query !== '') {
            $baseQb
                ->andWhere('LOWER(person.fullName) LIKE :query')
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
        }

        $countQb = clone $baseQb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $baseQb
            ->orderBy('person.fullName', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getArrayResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function findAlgeriaSeniorPlayerBySlug(string $slug): ?array
    {
        $matchedPlayer = $this->findSeniorPlayerRecordBySlug($slug);
        if ($matchedPlayer === null) {
            return null;
        }

        $playerId = (int) $matchedPlayer['id'];
        $personId = (int) $matchedPlayer['personId'];
        $galleryPhotos = $this->fetchPlayerGalleryPhotos($personId);
        $lineupStats = $this->fetchLineupStats($playerId);
        $disciplineStats = $this->fetchDisciplineStats($playerId);
        $capBounds = $this->fetchPlayerCapBounds($playerId);

        return [
            'id' => $playerId,
            'slug' => $slug,
            'fullName' => $matchedPlayer['fullName'],
            'photoUrl' => $matchedPlayer['photoUrl'],
            'featurePhotoUrl' => $matchedPlayer['featurePhotoUrl'],
            'galleryPhotos' => $galleryPhotos,
            'profile' => [
                'birthDate' => $this->dateFormatter->short($matchedPlayer['birthDate']),
                'birthCity' => $matchedPlayer['birthCityName'],
                'birthRegion' => $matchedPlayer['birthRegionName'],
                'birthCountry' => $matchedPlayer['birthCountryName'],
                'nationalityCountry' => $matchedPlayer['nationalityCountryName'],
                'primaryPositionCode' => $matchedPlayer['primaryPositionCode'],
                'primaryPositionLabel' => $matchedPlayer['primaryPositionLabel'],
            ],
            'stats' => [
                'starts' => $lineupStats['starts'],
                'subIn' => $lineupStats['subIn'],
                'captaincies' => $lineupStats['captaincies'],
                'goals' => $disciplineStats['goals'],
                'yellowCards' => $disciplineStats['yellowCards'],
                'redCards' => $disciplineStats['redCards'],
                'firstCapDate' => $capBounds['firstCapDate'],
                'lastCapDate' => $capBounds['lastCapDate'],
                'duelsWon' => $this->fetchDuelsWon($playerId),
            ],
            'appearanceOptions' => [
                'years' => $this->fetchPlayerAppearanceYears($playerId),
                'competitions' => $this->fetchPlayerAppearanceCompetitions($playerId),
            ],
            'appearancesMeta' => [
                'total' => $capBounds['total'],
            ],
        ];
    }

    /**
     * @param array{
     *     seasonName?: ?string,
     *     teamIso3?: ?string,
     *     competitionId?: ?int
     * } $filters
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}|null
     */
    public function findAlgeriaSeniorPlayerAppearancesBySlug(
        string $slug,
        array $filters,
        int $page,
        int $itemsPerPage
    ): ?array {
        $matchedPlayer = $this->findSeniorPlayerRecordBySlug($slug);
        if ($matchedPlayer === null) {
            return null;
        }

        $playerId = (int) $matchedPlayer['id'];
        $offset = max(0, ($page - 1) * $itemsPerPage);
        [$joinsSql, $whereSql, $params] = $this->buildPlayerAppearancesQueryParts($playerId, $filters);

        $total = (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(DISTINCT f.id)
            {$joinsSql}
            {$whereSql}",
            $params
        );

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT
                f.id AS fixtureId,
                f.external_match_no AS externalMatchNo,
                teamA.display_name AS countryA,
                teamB.display_name AS countryB,
                countryA.iso2 AS countryCodeA,
                countryB.iso2 AS countryCodeB,
                editions.editions AS editions,
                stages.stages AS stages,
                competitions.competitions AS competitions,
                scoreA.score AS scoreA,
                scoreB.score AS scoreB,
                COALESCE(categoryA.name, fixtureCategory.name, '') AS categoryA,
                COALESCE(categoryB.name, fixtureCategory.name, '') AS categoryB,
                f.match_date AS date,
                season.name AS season,
                f.is_official AS isOfficial,
                f.played AS played,
                city.name AS city,
                stadium.name AS stadium,
                country.name AS countryStadiumName,
                f.notes AS notes,
                COALESCE(competitions.competitionLabel, '') AS competitionLabel
            {$joinsSql}
            LEFT JOIN (
                SELECT
                    fe.fixture_id,
                    GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR '||') AS editions
                FROM fixture_edition fe
                INNER JOIN edition e ON e.id = fe.edition_id
                GROUP BY fe.fixture_id
            ) editions ON editions.fixture_id = f.id
            LEFT JOIN (
                SELECT
                    fs.fixture_id,
                    GROUP_CONCAT(DISTINCT s.name ORDER BY s.sort_order, s.name SEPARATOR '||') AS stages
                FROM fixture_stage fs
                INNER JOIN stage s ON s.id = fs.stage_id
                GROUP BY fs.fixture_id
            ) stages ON stages.fixture_id = f.id
            LEFT JOIN (
                SELECT
                    fc.fixture_id,
                    GROUP_CONCAT(DISTINCT CONCAT(c.id, '::', c.name) ORDER BY c.name SEPARATOR '||') AS competitions,
                    GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ' | ') AS competitionLabel
                FROM fixture_competition fc
                INNER JOIN competition c ON c.id = fc.competition_id
                GROUP BY fc.fixture_id
            ) competitions ON competitions.fixture_id = f.id
            {$whereSql}
            GROUP BY f.id
            ORDER BY f.match_date DESC, f.id DESC
            LIMIT {$itemsPerPage} OFFSET {$offset}",
            $params
        );

        foreach ($rows as &$row) {
            $row['editions'] = is_string($row['editions']) && $row['editions'] !== ''
                ? explode('||', $row['editions'])
                : null;
            $row['stages'] = is_string($row['stages']) && $row['stages'] !== ''
                ? explode('||', $row['stages'])
                : null;
            $row['competitions'] = $this->parsePlayerAppearanceCompetitions($row['competitions'] ?? null);
        }
        unset($row);

        return [
            'items' => $rows,
            'total' => $total,
        ];
    }

    /** @return array<int, array{id:int,imageUrl:string,caption:?string,sortOrder:int}> */
    private function fetchPlayerGalleryPhotos(int $personId): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    pp.id,
                    pp.image_url AS imageUrl,
                    pp.caption,
                    pp.sort_order AS sortOrder
                FROM person_photo pp
                WHERE pp.person_id = :personId
                ORDER BY pp.sort_order ASC, pp.id ASC
            SQL,
            ['personId' => $personId]
        );
    }

    private function createAlgeriaSeniorPlayersQueryBuilder()
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.person', 'person')
            ->innerJoin('App\\Entity\\PersonAssignment', 'assignment', 'WITH', 'assignment.person = person')
            ->innerJoin('assignment.team', 'team')
            ->innerJoin('assignment.role', 'assignmentRole')
            ->innerJoin('team.nationalTeam', 'nationalTeam')
            ->innerJoin('nationalTeam.country', 'country')
            ->where('person.nationalityCountry = :nationalityCountryId')
            ->andWhere(
                'NOT EXISTS (
                    SELECT 1 FROM App\\Entity\\PersonAssignment newerMembership
                    WHERE newerMembership.person = person
                    AND newerMembership.role = assignmentRole
                    AND (
                        COALESCE(newerMembership.fromDate, newerMembership.toDate) > COALESCE(assignment.fromDate, assignment.toDate)
                        OR (
                            COALESCE(newerMembership.fromDate, newerMembership.toDate) = COALESCE(assignment.fromDate, assignment.toDate)
                            AND newerMembership.id > assignment.id
                        )
                    )
                )'
            )
            ->andWhere('UPPER(team.teamType) = :teamTypeNational')
            ->andWhere('UPPER(assignmentRole.code) = :playerRoleCode')
            ->setParameter('teamTypeNational', 'NATIONAL')
            ->setParameter('playerRoleCode', 'PLAYER')
            ->setParameter('nationalityCountryId', self::ALGERIA_COUNTRY_ID);
    }

    private function findSeniorPlayerRecordBySlug(string $slug): ?array
    {
        $players = $this->createAlgeriaSeniorPlayersQueryBuilder()
            ->select(
                'DISTINCT p.id AS id',
                'person.id AS personId',
                'person.fullName AS fullName',
                'person.photoUrl AS photoUrl',
                'person.featurePhotoUrl AS featurePhotoUrl',
                'person.birthDate AS birthDate',
                'birthCity.name AS birthCityName',
                'birthRegion.name AS birthRegionName',
                'birthCountry.name AS birthCountryName',
                'nationalityCountry.name AS nationalityCountryName',
                'position.code AS primaryPositionCode',
                'position.label AS primaryPositionLabel'
            )
            ->leftJoin('person.birthCity', 'birthCity')
            ->leftJoin('person.birthRegion', 'birthRegion')
            ->leftJoin('person.birthCountry', 'birthCountry')
            ->leftJoin('person.nationalityCountry', 'nationalityCountry')
            ->leftJoin('p.primaryPosition', 'position')
            ->getQuery()
            ->getArrayResult();

        $slugger = new AsciiSlugger();

        foreach ($players as $player) {
            $playerSlug = $slugger->slug((string) $player['fullName'])->lower()->toString();

            if ($playerSlug === $slug) {
                return $player;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchMemberships(int $playerId): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    ptm.id,
                    player_entity.id as playerId,
                    ptm.from_date AS fromDate,
                    ptm.to_date AS toDate,
                    CASE WHEN ptm.to_date IS NULL THEN 1 ELSE 0 END AS isCurrent,
                    NULL AS sourceNote,
                    t.display_name AS teamDisplayName,
                    t.team_type AS teamType,
                    c.name AS clubName,
                    country.name AS countryName,
                    nt.name AS nationalTeamName
                FROM person_assignment ptm
                INNER JOIN player player_entity ON player_entity.person_id = ptm.person_id
                INNER JOIN role role ON role.id = ptm.role_id AND role.code = 'PLAYER'
                LEFT JOIN team t ON t.id = ptm.team_id
                LEFT JOIN club c ON c.id = t.club_id
                LEFT JOIN national_team nt ON nt.id = t.national_team_id
                LEFT JOIN country country ON country.id = nt.country_id
                WHERE player_entity.id = :playerId
                ORDER BY COALESCE(ptm.from_date, ptm.to_date) DESC, ptm.id DESC
            SQL,
            ['playerId' => $playerId]
        );
    }

    private function fetchNationalStats(int $playerId): array
    {
        $records = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    pns.id,
                    pns.caps,
                    pns.goals,
                    pns.from_date AS fromDate,
                    pns.to_date AS toDate,
                    pns.source_note AS sourceNote,
                    t.display_name AS teamDisplayName
                FROM player_national_stats pns
                LEFT JOIN team t ON t.id = pns.team_id
                WHERE pns.player_id = :playerId
                ORDER BY COALESCE(pns.to_date, pns.from_date) DESC, pns.id DESC
            SQL,
            ['playerId' => $playerId]
        );

        $caps = 0;
        $goals = 0;

        foreach ($records as $record) {
            $caps += (int) ($record['caps'] ?? 0);
            $goals += (int) ($record['goals'] ?? 0);
        }

        return [
            'totals' => [
                'caps' => $caps,
                'goals' => $goals,
            ],
            'records' => $records,
        ];
    }

    /** @return array{0: string, 1: string, 2: array<string, mixed>} */
    private function buildPlayerAppearancesQueryParts(int $playerId, array $filters): array
    {
        $joinsSql = <<<'SQL'
            FROM scoresheet_lineup sl
            INNER JOIN scoresheet s ON s.id = sl.scoresheet_id
            INNER JOIN fixture f ON f.id = s.fixture_id
            LEFT JOIN fixture_participant scoreA ON scoreA.fixture_id = f.id AND scoreA.role = 'A'
            LEFT JOIN fixture_participant scoreB ON scoreB.fixture_id = f.id AND scoreB.role = 'B'
            LEFT JOIN team teamA ON teamA.id = scoreA.team_id
            LEFT JOIN team teamB ON teamB.id = scoreB.team_id
            LEFT JOIN national_team nationalTeamA ON nationalTeamA.id = teamA.national_team_id
            LEFT JOIN national_team nationalTeamB ON nationalTeamB.id = teamB.national_team_id
            LEFT JOIN country countryA ON countryA.id = nationalTeamA.country_id
            LEFT JOIN country countryB ON countryB.id = nationalTeamB.country_id
            LEFT JOIN category categoryA ON categoryA.id = nationalTeamA.category_id
            LEFT JOIN category categoryB ON categoryB.id = nationalTeamB.category_id
            LEFT JOIN category fixtureCategory ON fixtureCategory.id = f.category_id
            LEFT JOIN season season ON season.id = f.season_id
            LEFT JOIN city city ON city.id = f.city_id
            LEFT JOIN stadium stadium ON stadium.id = f.stadium_id
            LEFT JOIN country country ON country.id = f.country_id
        SQL;

        $conditions = ['sl.player_id = :playerId'];
        $params = ['playerId' => $playerId, 'algeriaCountryId' => self::ALGERIA_COUNTRY_ID];

        if (!empty($filters['seasonName'])) {
            $conditions[] = 'season.name = :seasonName';
            $params['seasonName'] = $filters['seasonName'];
        }

        if (!empty($filters['teamIso3'])) {
            $conditions[] = '(
                (nationalTeamA.country_id = :algeriaCountryId AND countryB.iso3 = :teamIso3)
                OR
                (nationalTeamB.country_id = :algeriaCountryId AND countryA.iso3 = :teamIso3)
            )';
            $params['teamIso3'] = $filters['teamIso3'];
        }

        if (!empty($filters['competitionId'])) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM fixture_competition fc_filter
                WHERE fc_filter.fixture_id = f.id
                  AND fc_filter.competition_id = :competitionId
            )';
            $params['competitionId'] = (int) $filters['competitionId'];
        }

        return [$joinsSql, 'WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function fetchPlayerCapBounds(int $playerId): array
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    COUNT(DISTINCT f.id) AS total,
                    MIN(f.match_date) AS firstCapDate,
                    MAX(f.match_date) AS lastCapDate
                FROM scoresheet_lineup sl
                INNER JOIN scoresheet s ON s.id = sl.scoresheet_id
                INNER JOIN fixture f ON f.id = s.fixture_id
                WHERE sl.player_id = :playerId
            SQL,
            ['playerId' => $playerId]
        );

        return [
            'total' => (int) ($result['total'] ?? 0),
            'firstCapDate' => isset($result['firstCapDate']) ? substr((string) $result['firstCapDate'], 0, 4) : null,
            'lastCapDate' => isset($result['lastCapDate']) ? substr((string) $result['lastCapDate'], 0, 4) : null,
        ];
    }

    /** @return array<int, int> */
    private function fetchPlayerAppearanceYears(int $playerId): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT YEAR(f.match_date) AS seasonYear
                FROM scoresheet_lineup sl
                INNER JOIN scoresheet s ON s.id = sl.scoresheet_id
                INNER JOIN fixture f ON f.id = s.fixture_id
                WHERE sl.player_id = :playerId
                  AND f.match_date IS NOT NULL
                ORDER BY seasonYear DESC
            SQL,
            ['playerId' => $playerId]
        );

        return array_values(array_map('intval', $rows));
    }

    /** @return array<int, array{id:int,name:string}> */
    private function fetchPlayerAppearanceCompetitions(int $playerId): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT DISTINCT c.id, c.name
                FROM scoresheet_lineup sl
                INNER JOIN scoresheet s ON s.id = sl.scoresheet_id
                INNER JOIN fixture f ON f.id = s.fixture_id
                INNER JOIN fixture_competition fc ON fc.fixture_id = f.id
                INNER JOIN competition c ON c.id = fc.competition_id
                WHERE sl.player_id = :playerId
                ORDER BY c.name ASC
            SQL,
            ['playerId' => $playerId]
        );
    }

    /** @return array<int, array{id:int,name:string}> */
    private function parsePlayerAppearanceCompetitions(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $items = [];

        foreach (explode('||', $value) as $competition) {
            [$id, $name] = array_pad(explode('::', $competition, 2), 2, null);
            if ($id === null || $name === null || $name === '') {
                continue;
            }

            $items[] = [
                'id' => (int) $id,
                'name' => $name,
            ];
        }

        return $items;
    }

    private function fetchLineupStats(int $playerId): array
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    SUM(CASE WHEN sl.lineup_role = 'STARTER' THEN 1 ELSE 0 END) AS starts,
                    SUM(CASE WHEN sl.lineup_role = 'SUB' THEN 1 ELSE 0 END) AS subIn,
                    SUM(CASE WHEN sl.is_captain = 1 THEN 1 ELSE 0 END) AS captaincies
                FROM scoresheet_lineup sl
                WHERE sl.player_id = :playerId
            SQL,
            ['playerId' => $playerId]
        );

        return [
            'starts' => (int) ($result['starts'] ?? 0),
            'subIn' => (int) ($result['subIn'] ?? 0),
            'captaincies' => (int) ($result['captaincies'] ?? 0),
        ];
    }

    private function fetchDisciplineStats(int $playerId): array
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM match_goal mg WHERE mg.scorer_id = :playerId) AS goals,
                    (SELECT COUNT(*) FROM match_card mc WHERE mc.player_id = :playerId AND LOWER(mc.card_type) = 'y') AS yellowCards,
                    (SELECT COUNT(*) FROM match_card mc WHERE mc.player_id = :playerId AND LOWER(mc.card_type) = 'r') AS redCards
            SQL,
            ['playerId' => $playerId]
        );

        return [
            'goals' => (int) ($result['goals'] ?? 0),
            'yellowCards' => (int) ($result['yellowCards'] ?? 0),
            'redCards' => (int) ($result['redCards'] ?? 0),
        ];
    }

    private function fetchDuelsWon(int $playerId): int
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT COUNT(DISTINCT sl.scoresheet_id) AS duelsWon
                FROM scoresheet_lineup sl
                INNER JOIN scoresheet s ON s.id = sl.scoresheet_id
                INNER JOIN fixture_participant fp ON fp.fixture_id = s.fixture_id AND fp.team_id = sl.team_id
                WHERE sl.player_id = :playerId
                  AND fp.outcome = 1
            SQL,
            ['playerId' => $playerId]
        );

        return (int) ($result['duelsWon'] ?? 0);
    }

    private function slugify(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $normalized = mb_strtolower($ascii);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?: '';

        return trim($slug, '-');
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }

        if (is_string($date) && $date !== '') {
            try {
                return (new \DateTimeImmutable($date))->format('d/m/Y');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function formatPeriod(mixed $fromDate, mixed $toDate, bool $isCurrent): string
    {
        $from = $this->formatDate($fromDate);
        $to = $isCurrent ? 'Présent' : ($this->formatDate($toDate) ?? 'Date inconnue');

        if ($from === null) {
            return $isCurrent ? 'Depuis une date inconnue' : 'Période non renseignée';
        }

        return sprintf('%s → %s', $from, $to);
    }
}
