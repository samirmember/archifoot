<?php

namespace App\Repository;

use App\Entity\PlayerTeamMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PlayerTeamMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerTeamMembership::class);
    }

    public function findCurrentForPlayer(int $playerId): ?PlayerTeamMembership
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.player = :pid')
            ->andWhere('m.isCurrent = true')
            ->setParameter('pid', $playerId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // public function findForPlayerAtDate(int $playerId, \DateTimeInterface $date): ?PlayerTeamMembership
    // {
    //     $d = \DateTimeImmutable::createFromInterface($date);

    //     return $this->createQueryBuilder('m')
    //         ->andWhere('m.player = :pid')
    //         ->andWhere('(m.fromDate IS NULL OR m.fromDate <= :d)')
    //         ->andWhere('(m.toDate IS NULL OR m.toDate >= :d)')
    //         ->setParameter('pid', $playerId)
    //         ->setParameter('d', $d)
    //         ->addOrderBy('m.fromDate', 'DESC')
    //         ->setMaxResults(1)
    //         ->getQuery()
    //         ->getOneOrNullResult();
    // }

    public function findClubAtDate(int $playerId, \DateTimeInterface $date): ?PlayerTeamMembership
    {
        $d = \DateTimeImmutable::createFromInterface($date);

        return $this->createQueryBuilder('m')
            ->addSelect('t')
            ->join('m.team', 't')
            ->andWhere('m.player = :pid')
            ->andWhere('(m.fromDate IS NULL OR m.fromDate <= :d)')
            ->andWhere('(m.toDate IS NULL OR m.toDate >= :d)')
            ->setParameter('pid', $playerId)
            ->setParameter('d', $d)
            ->addOrderBy('m.fromDate', 'DESC')   // si plusieurs périodes possibles, on prend la plus récente
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * Retourne toutes les adhésions d'un joueur à des clubs.
     *
     * @return PlayerTeamMembership[]
     */
    public function findAllForPlayer(int $playerId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.player = :pid')
            ->setParameter('pid', $playerId)
            ->orderBy('m.fromDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentClub(int $playerId): ?PlayerTeamMembership
    {
        return $this->createQueryBuilder('m')
            ->addSelect('t')
            ->join('m.team', 't')
            ->andWhere('m.player = :pid')
            ->andWhere('m.isCurrent = true')
            ->setParameter('pid', $playerId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findClubAtFixture(int $playerId, int $fixtureId): ?array
    {
        $sql = <<<SQL
SELECT t.id AS team_id, t.display_name AS club_name, f.match_date
FROM fixture f
JOIN player_team_membership m
ON m.player_id = :playerId
AND (m.from_date IS NULL OR m.from_date <= f.match_date)
AND (m.to_date   IS NULL OR m.to_date   >= f.match_date)
JOIN team t ON t.id = m.team_id
WHERE f.id = :fixtureId
ORDER BY (m.from_date IS NULL) ASC, m.from_date DESC
LIMIT 1
SQL;

        return $this->getEntityManager()->getConnection()->fetchAssociative($sql, [
            'playerId' => $playerId,
            'fixtureId' => $fixtureId,
        ]) ?: null;
    }

}