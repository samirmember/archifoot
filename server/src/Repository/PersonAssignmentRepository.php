<?php

namespace App\Repository;

use App\Entity\PersonAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PersonAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonAssignment::class);
    }

    /**
     * Exemple: trouver le HEAD_COACH d'une équipe à une date donnée (historique)
     */
    public function findHeadCoachAtDate(int $teamId, \DateTimeImmutable $date): ?PersonAssignment
    {
        return $this->createQueryBuilder('tpr')
            ->innerJoin('tpr.role', 'r')
            ->andWhere('tpr.team = :teamId')
            ->andWhere('r.code = :roleCode')
            ->andWhere('(tpr.fromDate IS NULL OR tpr.fromDate <= :d)')
            ->andWhere('(tpr.toDate   IS NULL OR tpr.toDate   >= :d)')
            ->setParameter('teamId', $teamId)
            ->setParameter('roleCode', 'HEAD_COACH')
            ->setParameter('d', $date->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Liste des rôles d'une personne sur une équipe (pour profil joueur/coach)
     */
    public function findPersonHistory(int $personId, int $teamId): array
    {
        return $this->createQueryBuilder('tpr')
            ->andWhere('tpr.person = :personId')
            ->andWhere('tpr.team = :teamId')
            ->setParameter('personId', $personId)
            ->setParameter('teamId', $teamId)
            ->orderBy('tpr.fromDate', 'ASC')
            ->addOrderBy('tpr.toDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}