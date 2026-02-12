<?php

namespace App\Repository;

use App\Entity\Coach;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coach>
 */
class CoachRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coach::class);
    }

    /**
     * @return array{items: array<int, array{id:int,fullName:string,role:?string,nationality:?string}>, total:int}
     */
    public function findCoaches(string $query, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.person', 'person')
            ->leftJoin('person.nationalityCountry', 'nationalityCountry')
            ->select('c.id AS id, person.fullName AS fullName, c.role AS role, nationalityCountry.name AS nationality');

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
}
