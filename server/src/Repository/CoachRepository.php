<?php

namespace App\Repository;

use App\Entity\Coach;
use App\Entity\Role;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @extends ServiceEntityRepository<Coach>
 */
class CoachRepository extends ServiceEntityRepository
{
    private const ALGERIA_NAMES = ['algérie', 'algerie'];
    private const ALGERIA_ISO3 = 'DZA';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coach::class);
    }

    /**
     * @return array{items: array<int, array{id:int,fullName:string,role:?string,nationality:?string,photoUrl:?string}>, total:int}
     */
    public function findCoaches(string $query, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.person', 'person')
            ->leftJoin('person.nationalityCountry', 'nationalityCountry')
            ->leftJoin(Role::class, 'role', 'WITH', 'role.code = c.role')
            ->select('c.id AS id, person.fullName AS fullName, role.label AS roleName, person.photoUrl AS photoUrl, nationalityCountry.name AS nationality');

        $qb
            ->andWhere('(LOWER(nationalityCountry.name) IN (:algeriaNames) OR UPPER(nationalityCountry.iso3) = :algeriaIso3)')
            ->setParameter('algeriaNames', self::ALGERIA_NAMES)
            ->setParameter('algeriaIso3', self::ALGERIA_ISO3);

        if ($query !== '') {
            $qb
                ->andWhere('LOWER(person.fullName) LIKE :query')
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
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

    public function findSeniorNationalTeamCoachBySlug(string $slug): ?array
    {
        $matchedCoach = $this->findSeniorNationalTeamCoachRecordBySlug($slug);
        if ($matchedCoach === null) {
            return null;
        }

        $stats = $this->fetchCoachMatchStats((int) $matchedCoach['personId']);

        return [
            'id' => (string) $matchedCoach['id'],
            'slug' => $slug,
            'fullName' => $matchedCoach['fullName'],
            'role' => Coach::ROLES[$matchedCoach['role']] ?? 'Entraîneur',
            'nationality' => $matchedCoach['nationality'] ?? 'Nationalité non renseignée',
            'birthDate' => isset($matchedCoach['birthDate']) && $matchedCoach['birthDate'] instanceof \DateTimeInterface
                ? $matchedCoach['birthDate']->format('Y-m-d')
                : null,
            'birthPlace' => $this->buildBirthPlace($matchedCoach['birthCityName'] ?? null, $matchedCoach['birthCountryName'] ?? null),
            'portraitUrl' => $matchedCoach['photoUrl'],
            'photoUrl' => $matchedCoach['photoUrl'],
            'contractUntil' => null,
            'preferredSystem' => null,
            'badges' => ['Données API'],
            'highlights' => [
                'trophies' => 0,
                'matchCount' => $stats['matchCount'],
                'wins' => $stats['wins'],
                'draws' => $stats['draws'],
                'losses' => $stats['losses'],
                'goalsFor' => $stats['goalsFor'],
                'goalsAgainst' => $stats['goalsAgainst'],
                'cleanSheets' => $stats['cleanSheets'],
                'debutMatch' => $stats['debutMatch'],
                'lastMatch' => $stats['lastMatch'],
            ],
            'appearanceOptions' => [
                'years' => $this->fetchCoachAppearanceYears((int) $matchedCoach['personId']),
                'competitions' => $this->fetchCoachAppearanceCompetitions((int) $matchedCoach['personId']),
            ],
            'appearancesMeta' => [
                'total' => $stats['matchCount'],
            ],
            'biography' => 'Biographie indisponible.',
            'careerPath' => [],
            'competitionStats' => [],
            'milestones' => [],
            'staff' => [],
            'futureDataPlaceholders' => [
                ['label' => 'xG créé / match', 'value' => 'À connecter via data provider'],
                ['label' => 'PPDA défensif', 'value' => 'À connecter via data provider'],
                ['label' => 'Moyenne d’âge XI type', 'value' => 'À connecter via base joueurs'],
                ['label' => 'Indice de rotation', 'value' => 'À connecter via feuille de match'],
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
    public function findSeniorNationalTeamCoachAppearancesBySlug(
        string $slug,
        array $filters,
        int $page,
        int $itemsPerPage
    ): ?array {
        $matchedCoach = $this->findSeniorNationalTeamCoachRecordBySlug($slug);
        if ($matchedCoach === null) {
            return null;
        }

        $personId = (int) $matchedCoach['personId'];
        $offset = max(0, ($page - 1) * $itemsPerPage);
        [$joinsSql, $whereSql, $params, $types] = $this->buildCoachAppearancesQueryParts($personId, $filters);

        $total = (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(DISTINCT f.id)
            {$joinsSql}
            {$whereSql}",
            $params,
            $types
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
            $params,
            $types
        );

        foreach ($rows as &$row) {
            $row['editions'] = is_string($row['editions']) && $row['editions'] !== ''
                ? explode('||', $row['editions'])
                : null;
            $row['stages'] = is_string($row['stages']) && $row['stages'] !== ''
                ? explode('||', $row['stages'])
                : null;
            $row['competitions'] = $this->parseAppearanceCompetitions($row['competitions'] ?? null);
        }
        unset($row);

        return [
            'items' => $rows,
            'total' => $total,
        ];
    }

    /** @return array{matchCount:int,wins:int,draws:int,losses:int,goalsFor:int,goalsAgainst:int,cleanSheets:int,debutMatch:?string,lastMatch:?string} */
    private function fetchCoachMatchStats(int $personId): array
    {
        [$joinsSql, $whereSql, $params, $types] = $this->buildCoachAppearancesQueryParts($personId, []);

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT
                f.id AS fixtureId,
                f.match_date AS match_date,
                COALESCE(
                    CASE
                        WHEN scoreA.team_id = ssf.team_id THEN scoreA.score
                        ELSE scoreB.score
                    END,
                    0
                ) AS score_algeria,
                COALESCE(
                    CASE
                        WHEN scoreA.team_id = ssf.team_id THEN scoreB.score
                        ELSE scoreA.score
                    END,
                    0
                ) AS score_opponent
            {$joinsSql}
            {$whereSql}
            GROUP BY f.id
            ORDER BY f.match_date ASC, f.id ASC",
            $params,
            $types
        );

        $stats = [
            'matchCount' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goalsFor' => 0,
            'goalsAgainst' => 0,
            'cleanSheets' => 0,
            'debutMatch' => null,
            'lastMatch' => null,
        ];

        foreach ($rows as $index => $row) {
            $for = (int) $row['score_algeria'];
            $against = (int) $row['score_opponent'];

            $stats['matchCount']++;
            $stats['goalsFor'] += $for;
            $stats['goalsAgainst'] += $against;

            if ($for > $against) {
                $stats['wins']++;
            } elseif ($for === $against) {
                $stats['draws']++;
            } else {
                $stats['losses']++;
            }

            if ($against === 0) {
                $stats['cleanSheets']++;
            }

            $date = substr((string) $row['match_date'], 0, 10) ?: null;
            if ($index === 0) {
                $stats['debutMatch'] = $date;
            }
            $stats['lastMatch'] = $date;
        }

        return $stats;
    }

    private function findSeniorNationalTeamCoachRecordBySlug(string $slug): ?array
    {
        $coaches = $this->createQueryBuilder('c')
            ->innerJoin('c.person', 'person')
            ->leftJoin('person.birthCity', 'birthCity')
            ->leftJoin('person.birthCountry', 'birthCountry')
            ->leftJoin('person.nationalityCountry', 'nationalityCountry')
            ->select(
                'c.id AS id',
                'person.id AS personId',
                'person.fullName AS fullName',
                'c.role AS role',
                'person.photoUrl AS photoUrl',
                'person.birthDate AS birthDate',
                'birthCity.name AS birthCityName',
                'birthCountry.name AS birthCountryName',
                'nationalityCountry.name AS nationality'
            )
            ->getQuery()
            ->getArrayResult();

        $slugger = new AsciiSlugger();

        foreach ($coaches as $coach) {
            $coachSlug = $slugger->slug((string) ($coach['fullName'] ?? ''))->lower()->toString();

            if ($coachSlug === $slug) {
                return $coach;
            }
        }

        return null;
    }

    /** @return array{0: string, 1: string, 2: array<string, mixed>, 3: array<string, mixed>} */
    private function buildCoachAppearancesQueryParts(int $personId, array $filters): array
    {
        $joinsSql = <<<'SQL'
FROM scoresheet_staff ssf
INNER JOIN scoresheet s ON s.id = ssf.scoresheet_id
INNER JOIN fixture f ON f.id = s.fixture_id
INNER JOIN fixture_participant algeriaParticipant ON algeriaParticipant.fixture_id = f.id AND algeriaParticipant.team_id = ssf.team_id
INNER JOIN team algeriaTeam ON algeriaTeam.id = algeriaParticipant.team_id
INNER JOIN national_team algeriaNationalTeam ON algeriaNationalTeam.id = algeriaTeam.national_team_id
INNER JOIN country algeriaCountry ON algeriaCountry.id = algeriaNationalTeam.country_id
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

        $conditions = [
            'ssf.person_id = :personId',
            "ssf.role IN ('HEAD_COACH', 'ASSISTANT_COACH')",
            'f.played = 1',
            '(
                LOWER(algeriaCountry.name) IN (:algeriaNames)
                OR UPPER(algeriaCountry.iso3) = :algeriaIso3
            )',
        ];
        $params = [
            'personId' => $personId,
            'algeriaNames' => self::ALGERIA_NAMES,
            'algeriaIso3' => self::ALGERIA_ISO3,
        ];
        $types = [
            'algeriaNames' => ArrayParameterType::STRING,
        ];

        if (!empty($filters['seasonName'])) {
            $conditions[] = 'season.name = :seasonName';
            $params['seasonName'] = $filters['seasonName'];
        }

        if (!empty($filters['teamIso3'])) {
            $conditions[] = '(
                (scoreA.team_id = ssf.team_id AND countryB.iso3 = :teamIso3)
                OR
                (scoreB.team_id = ssf.team_id AND countryA.iso3 = :teamIso3)
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

        return [$joinsSql, 'WHERE ' . implode(' AND ', $conditions), $params, $types];
    }

    /** @return array<int, int> */
    private function fetchCoachAppearanceYears(int $personId): array
    {
        [$joinsSql, $whereSql, $params, $types] = $this->buildCoachAppearancesQueryParts($personId, []);

        $rows = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            "SELECT DISTINCT YEAR(f.match_date) AS seasonYear
            {$joinsSql}
            {$whereSql}
              AND f.match_date IS NOT NULL
            ORDER BY seasonYear DESC",
            $params,
            $types
        );

        return array_values(array_map('intval', $rows));
    }

    /** @return array<int, array{id:int,name:string}> */
    private function fetchCoachAppearanceCompetitions(int $personId): array
    {
        [$joinsSql, $whereSql, $params, $types] = $this->buildCoachAppearancesQueryParts($personId, []);

        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT DISTINCT c.id, c.name
            {$joinsSql}
            INNER JOIN fixture_competition fc ON fc.fixture_id = f.id
            INNER JOIN competition c ON c.id = fc.competition_id
            {$whereSql}
            ORDER BY c.name ASC",
            $params,
            $types
        );
    }

    /** @return array<int, array{id:int,name:string}> */
    private function parseAppearanceCompetitions(mixed $value): array
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

    private function buildBirthPlace(?string $birthCityName, ?string $birthCountryName): ?string
    {
        $parts = array_filter([$birthCityName, $birthCountryName]);

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }
}
