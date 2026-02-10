<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * @return array{items: array<int, array{id:int,fullName:string,photoUrl:?string}>, total:int}
     */
    public function findAlgeriaSeniorPlayers(string $query, int $page, int $perPage): array
    {
        $baseQb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id AS id, person.fullName AS fullName, p.photoUrl AS photoUrl')
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
            ->setParameter('algeriaNames', ['algérie', 'algerie'])
            ->setParameter('algeriaIso3', 'DZA');

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
                'position.label AS position',
                'nationality.name AS nationality',
                'person.birthDate AS birthDate',
                'birthCity.name AS birthCity',
                'birthRegion.name AS birthRegion',
                'birthCountry.name AS birthCountry'
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
            $detail['birthCity'] ?? null,
            $detail['birthRegion'] ?? null,
            $detail['birthCountry'] ?? null,
        ]);

        return [
            'id' => $playerId,
            'slug' => $slug,
            'fullName' => $detail['fullName'],
            'photoUrl' => $detail['photoUrl'],
            'position' => $detail['position'],
            'nationality' => $detail['nationality'],
            'birthDateLabel' => $this->formatDate($detail['birthDate'] ?? null),
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
