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
                'birthCity.name AS birthCity',
                'birthRegion.name AS birthRegion',
                'birthCountry.name AS birthCountry',
                'nationalityCountry.name AS nationalityCountry',
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
                'birthCity' => $matchedPlayer['birthCity'],
                'birthRegion' => $matchedPlayer['birthRegion'],
                'birthCountry' => $matchedPlayer['birthCountry'],
                'nationalityCountry' => $matchedPlayer['nationalityCountry'],
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
}
