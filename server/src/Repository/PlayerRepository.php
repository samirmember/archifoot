<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    private const ALGERIA_NAMES = ['algérie', 'algerie'];
    private const ALGERIA_ISO3 = 'DZA';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * @return array{items: array<int, array{id:int,fullName:string,photoUrl:?string}>, total:int}
     */
    public function findAlgeriaSeniorPlayers(string $query, int $page, int $perPage): array
    {
        $baseQb = $this->createAlgeriaSeniorPlayersQueryBuilder()
            ->select('DISTINCT p.id AS id, person.fullName AS fullName, p.photoUrl AS photoUrl');

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
        $players = $this->createAlgeriaSeniorPlayersQueryBuilder()
            ->select(
                'DISTINCT p.id AS id',
                'person.fullName AS fullName',
                'p.photoUrl AS photoUrl',
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
        $matchedPlayer = null;

        foreach ($players as $player) {
            $playerSlug = $slugger->slug((string) $player['fullName'])->lower()->toString();

            if ($playerSlug === $slug) {
                $matchedPlayer = $player;
                break;
            }
        }

        if ($matchedPlayer === null) {
            return null;
        }

        $playerId = (int) $matchedPlayer['id'];
        $memberships = $this->fetchMemberships($playerId);
        $nationalStats = $this->fetchNationalStats($playerId);
        $lineupStats = $this->fetchLineupStats($playerId);
        $disciplineStats = $this->fetchDisciplineStats($playerId);

        return [
            'id' => $playerId,
            'slug' => $slug,
            'fullName' => $matchedPlayer['fullName'],
            'photoUrl' => $matchedPlayer['photoUrl'],
            'profile' => [
                'birthDate' => $matchedPlayer['birthDate']?->format('Y-m-d'),
                'birthCity' => $matchedPlayer['birthCityName'],
                'birthRegion' => $matchedPlayer['birthRegionName'],
                'birthCountry' => $matchedPlayer['birthCountryName'],
                'nationalityCountry' => $matchedPlayer['nationalityCountryName'],
                'primaryPositionCode' => $matchedPlayer['primaryPositionCode'],
                'primaryPositionLabel' => $matchedPlayer['primaryPositionLabel'],
            ],
            'memberships' => $memberships,
            'nationalStats' => $nationalStats,
            'stats' => [
                'caps' => $nationalStats['totals']['caps'],
                'goals' => $nationalStats['totals']['goals'],
                'starts' => $lineupStats['starts'],
                'benchAppearances' => $lineupStats['benchAppearances'],
                'captaincies' => $lineupStats['captaincies'],
                'scoredGoalsFromMatchEvents' => $disciplineStats['scoredGoalsFromMatchEvents'],
                'yellowCards' => $disciplineStats['yellowCards'],
                'redCards' => $disciplineStats['redCards'],
            ],
            'timeline' => [
                'memberships' => $memberships,
                'nationalStatRecords' => $nationalStats['records'],
            ],
            'futureDataPlaceholders' => [
                ['label' => 'Minutes jouées', 'value' => null],
                ['label' => 'Passes décisives', 'value' => null],
                ['label' => 'Tacles réussis', 'value' => null],
                ['label' => 'Duels gagnés', 'value' => null],
                ['label' => 'Distance parcourue', 'value' => null],
                ['label' => 'Expected Goals (xG)', 'value' => null],
            ],
        ];
    }

    private function createAlgeriaSeniorPlayersQueryBuilder()
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.person', 'person')
            ->innerJoin('App\\Entity\\PlayerTeamMembership', 'membership', 'WITH', 'membership.player = p')
            ->innerJoin('membership.team', 'team')
            ->innerJoin('team.nationalTeam', 'nationalTeam')
            ->innerJoin('nationalTeam.country', 'country')
            ->where('LOWER(country.name) IN (:algeriaNames) OR UPPER(country.iso3) = :algeriaIso3')
            ->andWhere(
                'NOT EXISTS (
                    SELECT 1 FROM App\\Entity\\PlayerTeamMembership newerMembership
                    WHERE newerMembership.player = p
                    AND (
                        COALESCE(newerMembership.fromDate, newerMembership.toDate) > COALESCE(membership.fromDate, membership.toDate)
                        OR (
                            COALESCE(newerMembership.fromDate, newerMembership.toDate) = COALESCE(membership.fromDate, membership.toDate)
                            AND newerMembership.id > membership.id
                        )
                    )
                )'
            )
            ->andWhere('UPPER(team.teamType) = :teamTypeNational')
            ->setParameter('teamTypeNational', 'NATIONAL')
            ->setParameter('algeriaNames', self::ALGERIA_NAMES)
            ->setParameter('algeriaIso3', self::ALGERIA_ISO3);
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchMemberships(int $playerId): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    ptm.id,
                    ptm.from_date AS fromDate,
                    ptm.to_date AS toDate,
                    ptm.is_current AS isCurrent,
                    ptm.source_note AS sourceNote,
                    t.display_name AS teamDisplayName,
                    t.team_type AS teamType,
                    c.name AS clubName,
                    country.name AS countryName,
                    nt.name AS nationalTeamName
                FROM player_team_membership ptm
                LEFT JOIN team t ON t.id = ptm.team_id
                LEFT JOIN club c ON c.id = t.club_id
                LEFT JOIN national_team nt ON nt.id = t.national_team_id
                LEFT JOIN country country ON country.id = nt.country_id
                WHERE ptm.player_id = :playerId
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

    private function fetchLineupStats(int $playerId): array
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    SUM(CASE WHEN sl.lineup_role = 'STARTER' THEN 1 ELSE 0 END) AS starts,
                    SUM(CASE WHEN sl.lineup_role = 'BENCH' THEN 1 ELSE 0 END) AS benchAppearances,
                    SUM(CASE WHEN sl.is_captain = 1 THEN 1 ELSE 0 END) AS captaincies
                FROM scoresheet_lineup sl
                WHERE sl.player_id = :playerId
            SQL,
            ['playerId' => $playerId]
        );

        return [
            'starts' => (int) ($result['starts'] ?? 0),
            'benchAppearances' => (int) ($result['benchAppearances'] ?? 0),
            'captaincies' => (int) ($result['captaincies'] ?? 0),
        ];
    }

    private function fetchDisciplineStats(int $playerId): array
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM match_goal mg WHERE mg.scorer_id = :playerId) AS scoredGoalsFromMatchEvents,
                    (SELECT COUNT(*) FROM match_card mc WHERE mc.player_id = :playerId AND LOWER(mc.card_type) = 'y') AS yellowCards,
                    (SELECT COUNT(*) FROM match_card mc WHERE mc.player_id = :playerId AND LOWER(mc.card_type) = 'r') AS redCards
            SQL,
            ['playerId' => $playerId]
        );

        return [
            'scoredGoalsFromMatchEvents' => (int) ($result['scoredGoalsFromMatchEvents'] ?? 0),
            'yellowCards' => (int) ($result['yellowCards'] ?? 0),
            'redCards' => (int) ($result['redCards'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAlgeriaSeniorPlayerProfileBySlug(string $slug): ?array
    {
        $playerRows = $this->createQueryBuilder('p')
            ->select('p.id AS id, person.fullName AS fullName')
            ->innerJoin('p.person', 'person')
            ->innerJoin('App\\Entity\\PlayerTeamMembership', 'membership', 'WITH', 'membership.player = p')
            ->innerJoin('membership.team', 'team')
            ->innerJoin('team.nationalTeam', 'nationalTeam')
            ->innerJoin('nationalTeam.country', 'country')
            ->where('UPPER(team.teamType) = :teamTypeNational')
            ->andWhere('LOWER(country.name) IN (:algeriaNames) OR UPPER(country.iso3) = :algeriaIso3')
            ->setParameter('teamTypeNational', 'NATIONAL')
            ->setParameter('algeriaNames', ['algérie', 'algerie'])
            ->setParameter('algeriaIso3', 'DZA')
            ->orderBy('person.fullName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $targetPlayer = null;
        foreach ($playerRows as $row) {
            if ($this->slugify((string) ($row['fullName'] ?? '')) === $slug) {
                $targetPlayer = $row;
                break;
            }
        }

        if ($targetPlayer === null) {
            return null;
        }

        $playerId = (int) $targetPlayer['id'];
        $detail = $this->createQueryBuilder('p')
            ->select(
                'p.id AS id',
                'person.fullName AS fullName',
                'p.photoUrl AS photoUrl',
                'position.label AS positionName',
                'nationality.name AS nationalityName',
                'person.birthDate AS birthDateName',
                'birthCity.name AS birthCityName',
                'birthRegion.name AS birthRegionName',
                'birthCountry.name AS birthCountrNamey'
            )
            ->leftJoin('p.person', 'person')
            ->leftJoin('p.primaryPosition', 'position')
            ->leftJoin('person.nationalityCountry', 'nationality')
            ->leftJoin('person.birthCity', 'birthCity')
            ->leftJoin('person.birthRegion', 'birthRegion')
            ->leftJoin('person.birthCountry', 'birthCountry')
            ->where('p.id = :playerId')
            ->setParameter('playerId', $playerId)
            ->getQuery()
            ->getOneOrNullResult();

        if ($detail === null) {
            return null;
        }

        $clubHistoryRows = $this->getEntityManager()
            ->createQuery(
                'SELECT team.displayName AS teamName, membership.fromDate AS fromDate, membership.toDate AS toDate, membership.isCurrent AS isCurrent
                 FROM App\\Entity\\PlayerTeamMembership membership
                 INNER JOIN membership.team team
                 WHERE membership.player = :playerId AND UPPER(team.teamType) = :teamTypeClub
                 ORDER BY membership.isCurrent DESC, membership.fromDate DESC, membership.id DESC'
            )
            ->setParameter('playerId', $playerId)
            ->setParameter('teamTypeClub', 'CLUB')
            ->getArrayResult();

        $currentClub = null;
        $clubHistory = [];
        foreach ($clubHistoryRows as $row) {
            if ($currentClub === null && !empty($row['teamName'])) {
                $currentClub = $row['teamName'];
            }

            $clubHistory[] = [
                'teamName' => $row['teamName'] ?: 'Club non renseigné',
                'periodLabel' => $this->formatPeriod($row['fromDate'] ?? null, $row['toDate'] ?? null, (bool) ($row['isCurrent'] ?? false)),
                'isCurrent' => (bool) ($row['isCurrent'] ?? false),
            ];
        }

        $nationalStatsRows = $this->getEntityManager()
            ->createQuery(
                'SELECT stats.caps AS caps, stats.goals AS goals
                 FROM App\\Entity\\PlayerNationalStats stats
                 INNER JOIN stats.team team
                 INNER JOIN team.nationalTeam nationalTeam
                 INNER JOIN nationalTeam.country country
                 WHERE stats.player = :playerId
                   AND UPPER(team.teamType) = :teamTypeNational
                   AND (LOWER(country.name) IN (:algeriaNames) OR UPPER(country.iso3) = :algeriaIso3)'
            )
            ->setParameter('playerId', $playerId)
            ->setParameter('teamTypeNational', 'NATIONAL')
            ->setParameter('algeriaNames', ['algérie', 'algerie'])
            ->setParameter('algeriaIso3', 'DZA')
            ->getArrayResult();

        $caps = 0;
        $goals = 0;
        foreach ($nationalStatsRows as $row) {
            $caps += (int) ($row['caps'] ?? 0);
            $goals += (int) ($row['goals'] ?? 0);
        }

        $lineupStats = $this->getEntityManager()
            ->createQuery(
                'SELECT
                    COUNT(lineup.id) AS caps,
                    SUM(CASE WHEN UPPER(COALESCE(lineup.lineupRole, \'\')) = \'STARTER\' THEN 1 ELSE 0 END) AS starts,
                    SUM(CASE WHEN UPPER(COALESCE(lineup.lineupRole, \'\')) IN (\'SUB\', \'SUBSTITUTE\', \'BENCH\') THEN 1 ELSE 0 END) AS subIn,
                    SUM(CASE WHEN lineup.isCaptain = true THEN 1 ELSE 0 END) AS captainMatches,
                    MAX(fixture.matchDate) AS lastCapDate,
                    MAX(lineup.shirtNumber) AS shirtNumber
                 FROM App\\Entity\\ScoresheetLineup lineup
                 LEFT JOIN lineup.scoresheet scoresheet
                 LEFT JOIN scoresheet.fixture fixture
                 INNER JOIN lineup.team team
                 INNER JOIN team.nationalTeam nationalTeam
                 INNER JOIN nationalTeam.country country
                 WHERE lineup.player = :playerId
                   AND UPPER(team.teamType) = :teamTypeNational
                   AND (LOWER(country.name) IN (:algeriaNames) OR UPPER(country.iso3) = :algeriaIso3)'
            )
            ->setParameter('playerId', $playerId)
            ->setParameter('teamTypeNational', 'NATIONAL')
            ->setParameter('algeriaNames', ['algérie', 'algerie'])
            ->setParameter('algeriaIso3', 'DZA')
            ->getSingleResult();

        $cardStats = $this->getEntityManager()
            ->createQuery(
                'SELECT
                    SUM(CASE WHEN UPPER(COALESCE(card.cardType, \'\')) IN (\'Y\', \'YC\') THEN 1 ELSE 0 END) AS yellowCards,
                    SUM(CASE WHEN UPPER(COALESCE(card.cardType, \'\')) IN (\'R\', \'RC\') THEN 1 ELSE 0 END) AS redCards
                 FROM App\\Entity\\MatchCard card
                 INNER JOIN card.team team
                 INNER JOIN team.nationalTeam nationalTeam
                 INNER JOIN nationalTeam.country country
                 WHERE card.player = :playerId
                   AND UPPER(team.teamType) = :teamTypeNational
                   AND (LOWER(country.name) IN (:algeriaNames) OR UPPER(country.iso3) = :algeriaIso3)'
            )
            ->setParameter('playerId', $playerId)
            ->setParameter('teamTypeNational', 'NATIONAL')
            ->setParameter('algeriaNames', ['algérie', 'algerie'])
            ->setParameter('algeriaIso3', 'DZA')
            ->getSingleResult();

        $birthChunks = array_filter([
            $detail['birthCityName'] ?? null,
            $detail['birthRegionName'] ?? null,
            $detail['birthCountryName'] ?? null,
        ]);

        return [
            'id' => $playerId,
            'slug' => $slug,
            'fullName' => $detail['fullName'],
            'photoUrl' => $detail['photoUrl'],
            'position' => $detail['positionName'],
            'nationality' => $detail['nationalityName'],
            'birthDateLabel' => $this->formatDate($detail['birthDateName'] ?? null),
            'birthPlace' => count($birthChunks) ? implode(', ', $birthChunks) : null,
            'currentClub' => $currentClub,
            'shirtNumber' => isset($lineupStats['shirtNumber']) && $lineupStats['shirtNumber'] !== null
                ? (string) $lineupStats['shirtNumber']
                : null,
            'stats' => [
                'caps' => max($caps, (int) ($lineupStats['caps'] ?? 0)),
                'goals' => $goals,
                'starts' => (int) ($lineupStats['starts'] ?? 0),
                'subIn' => (int) ($lineupStats['subIn'] ?? 0),
                'yellowCards' => (int) ($cardStats['yellowCards'] ?? 0),
                'redCards' => (int) ($cardStats['redCards'] ?? 0),
                'captainMatches' => (int) ($lineupStats['captainMatches'] ?? 0),
                'lastCapDate' => $this->formatDate($lineupStats['lastCapDate'] ?? null),
            ],
            'clubHistory' => $clubHistory,
            'futureStats' => [
                [
                    'key' => 'minutes-played',
                    'title' => 'Minutes jouées',
                    'description' => 'Bloc prévu pour afficher le volume de minutes en sélection (à brancher sur les feuilles de match détaillées).',
                    'dynamic' => false,
                ],
                [
                    'key' => 'xg-xa',
                    'title' => 'xG / xA internationaux',
                    'description' => 'Bloc analytique avancé pour mesurer la qualité des occasions créées et converties.',
                    'dynamic' => false,
                ],
                [
                    'key' => 'def-actions',
                    'title' => 'Actions défensives',
                    'description' => 'Interceptions, tacles gagnés, duels défensifs : idéal pour valoriser les profils défensifs.',
                    'dynamic' => false,
                ],
                [
                    'key' => 'passing-profile',
                    'title' => 'Profil de passe',
                    'description' => 'Précision de passe, passes progressives et passes clés en sélection.',
                    'dynamic' => false,
                ],
            ],
        ];
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
