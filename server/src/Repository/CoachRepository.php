<?php

namespace App\Repository;

use App\Entity\Coach;
use App\Entity\Role;
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
        $matchedCoach = null;

        foreach ($coaches as $coach) {
            $coachSlug = $slugger->slug((string) ($coach['fullName'] ?? ''))->lower()->toString();

            if ($coachSlug === $slug) {
                $matchedCoach = $coach;
                break;
            }
        }

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

    /** @return array{matchCount:int,wins:int,draws:int,losses:int,goalsFor:int,goalsAgainst:int,cleanSheets:int,debutMatch:?string,lastMatch:?string} */
    private function fetchCoachMatchStats(int $personId): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    f.match_date AS match_date,
                    COALESCE(fpa.score, 0) AS score_algeria,
                    COALESCE(fpo.score, 0) AS score_opponent
                FROM scoresheet sc
                INNER JOIN fixture f ON f.id = sc.fixture_id
                INNER JOIN fixture_participant fpa ON fpa.fixture_id = f.id
                INNER JOIN team ta ON ta.id = fpa.team_id
                INNER JOIN national_team nta ON nta.id = ta.national_team_id
                INNER JOIN country ca ON ca.id = nta.country_id
                INNER JOIN fixture_participant fpo ON fpo.fixture_id = f.id AND fpo.role <> fpa.role
                INNER JOIN scoresheet_staff ssf ON ssf.scoresheet_id = sc.id
                WHERE ssf.person_id = :personId
                  AND ssf.role IN ('HEAD_COACH', 'ASSISTANT_COACH')
                  AND f.played = 1
                  AND (
                    LOWER(ca.name) IN (:algeriaNames)
                    OR UPPER(ca.iso3) = :algeriaIso3
                  )
                ORDER BY f.match_date ASC
            SQL,
            [
                'personId' => $personId,
                'algeriaNames' => self::ALGERIA_NAMES,
                'algeriaIso3' => self::ALGERIA_ISO3,
            ],
            [
                'algeriaNames' => \Doctrine\DBAL\ArrayParameterType::STRING,
            ]
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

    private function buildBirthPlace(?string $birthCityName, ?string $birthCountryName): ?string
    {
        $parts = array_filter([$birthCityName, $birthCountryName]);

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }
}
