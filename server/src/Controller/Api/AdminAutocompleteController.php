<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AdminAutocompleteController extends AbstractController
{
    #[Route('/api/admin-search/{type}', name: 'api_admin_search', methods: ['GET'])]
    public function __invoke(string $type, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 3) {
            return $this->json([]);
        }

        $results = match ($type) {
            'person' => $this->searchPerson($entityManager, $query),
            'player' => $this->searchPlayer($entityManager, $query),
            'country' => $this->searchByName($entityManager, 'App\\Entity\\Country', $query),
            'city' => $this->searchByName($entityManager, 'App\\Entity\\City', $query),
            'stadium' => $this->searchByName($entityManager, 'App\\Entity\\Stadium', $query),
            'matchday' => $this->searchByName($entityManager, 'App\\Entity\\Matchday', $query),
            'division' => $this->searchByName($entityManager, 'App\\Entity\\Division', $query),
            'category' => $this->searchByName($entityManager, 'App\\Entity\\Category', $query),
            default => [],
        };

        return $this->json($results);
    }

    private function searchByName(EntityManagerInterface $entityManager, string $entityClass, string $query): array
    {
        $rows = $entityManager->getRepository($entityClass)->createQueryBuilder('e')
            ->select('e.id AS value', 'e.name AS text')
            ->andWhere('LOWER(e.name) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($query).'%')
            ->orderBy('e.name', 'ASC')
            ->setMaxResults(30)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'value' => (string) $row['value'],
            'text' => (string) $row['text'],
        ], $rows);
    }

    private function searchPerson(EntityManagerInterface $entityManager, string $query): array
    {
        $rows = $entityManager->getRepository('App\\Entity\\Person')->createQueryBuilder('p')
            ->select('p.id AS value', 'p.fullName AS text')
            ->andWhere('LOWER(p.fullName) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($query).'%')
            ->orderBy('p.fullName', 'ASC')
            ->setMaxResults(30)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'value' => (string) $row['value'],
            'text' => (string) $row['text'],
        ], $rows);
    }

    private function searchPlayer(EntityManagerInterface $entityManager, string $query): array
    {
        $rows = $entityManager->getRepository('App\\Entity\\Player')->createQueryBuilder('p')
            ->leftJoin('p.person', 'person')
            ->select('p.id AS value', 'person.fullName AS text')
            ->andWhere('LOWER(person.fullName) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($query).'%')
            ->orderBy('person.fullName', 'ASC')
            ->setMaxResults(30)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'value' => (string) $row['value'],
            'text' => (string) ($row['text'] ?? ''),
        ], $rows);
    }
}
