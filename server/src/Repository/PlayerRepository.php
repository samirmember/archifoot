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
}
