<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repository\CoachRepository;
use Doctrine\ORM\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed for {$label}.\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

final class CoachMatchStatsTestConnection extends Connection
{
    public function __construct()
    {
    }

    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
        if (!str_contains($query, 'algeriaParticipant.outcome AS outcome_algeria')) {
            fwrite(STDERR, "Expected coach stats query to select Algeria outcome.\n");
            exit(1);
        }

        assertSameValue(42, $params['personId'] ?? null, 'personId parameter');

        return [
            ['fixtureId' => 1, 'match_date' => '2001-01-01 00:00:00', 'outcome_algeria' => 1, 'score_algeria' => 2, 'score_opponent' => 1],
            ['fixtureId' => 2, 'match_date' => '2002-02-02 00:00:00', 'outcome_algeria' => 2, 'score_algeria' => 0, 'score_opponent' => 0],
            ['fixtureId' => 3, 'match_date' => '2003-03-03 00:00:00', 'outcome_algeria' => 0, 'score_algeria' => 1, 'score_opponent' => 3],
            ['fixtureId' => 4, 'match_date' => '2004-04-04 00:00:00', 'outcome_algeria' => null, 'score_algeria' => 4, 'score_opponent' => 2],
            ['fixtureId' => 5, 'match_date' => '2005-05-05 00:00:00', 'outcome_algeria' => null, 'score_algeria' => 1, 'score_opponent' => 1],
            ['fixtureId' => 6, 'match_date' => '2006-06-06 00:00:00', 'outcome_algeria' => null, 'score_algeria' => 0, 'score_opponent' => 2],
        ];
    }
}

final class CoachMatchStatsTestEntityManager implements EntityManagerInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    private function unsupported(): never
    {
        throw new BadMethodCallException('Not implemented for this test.');
    }

    public function getRepository(string $className): EntityRepository
    {
        return $this->unsupported();
    }

    public function getCache(): Cache|null
    {
        return null;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->unsupported();
    }

    public function getExpressionBuilder(): Expr
    {
        return $this->unsupported();
    }

    public function beginTransaction(): void
    {
        $this->unsupported();
    }

    public function wrapInTransaction(callable $func): mixed
    {
        return $this->unsupported();
    }

    public function commit(): void
    {
        $this->unsupported();
    }

    public function rollback(): void
    {
        $this->unsupported();
    }

    public function createQuery(string $dql = ''): Query
    {
        return $this->unsupported();
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->unsupported();
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->unsupported();
    }

    public function find(string $className, mixed $id, Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null
    {
        return $this->unsupported();
    }

    public function persist(object $object): void
    {
        $this->unsupported();
    }

    public function remove(object $object): void
    {
        $this->unsupported();
    }

    public function clear(): void
    {
        $this->unsupported();
    }

    public function detach(object $object): void
    {
        $this->unsupported();
    }

    public function refresh(object $object, Doctrine\DBAL\LockMode|int|null $lockMode = null): void
    {
        $this->unsupported();
    }

    public function flush(): void
    {
        $this->unsupported();
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return new ClassMetadata($className);
    }

    public function getReference(string $entityName, mixed $id): object|null
    {
        return $this->unsupported();
    }

    public function close(): void
    {
        $this->unsupported();
    }

    public function lock(object $entity, Doctrine\DBAL\LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        $this->unsupported();
    }

    public function getEventManager(): EventManager
    {
        return $this->unsupported();
    }

    public function getConfiguration(): Configuration
    {
        return $this->unsupported();
    }

    public function isOpen(): bool
    {
        return true;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->unsupported();
    }

    public function newHydrator(string|int $hydrationMode): Doctrine\ORM\Internal\Hydration\AbstractHydrator
    {
        return $this->unsupported();
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->unsupported();
    }

    public function getFilters(): FilterCollection
    {
        return $this->unsupported();
    }

    public function isFiltersStateClean(): bool
    {
        return true;
    }

    public function hasFilters(): bool
    {
        return false;
    }

    public function initializeObject(object $obj): void
    {
        $this->unsupported();
    }

    public function isUninitializedObject(mixed $value): bool
    {
        return false;
    }

    public function contains(object $object): bool
    {
        return false;
    }
}

final class TestCoachRepository extends CoachRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function fetchCoachMatchStatsForTest(int $personId): array
    {
        $method = new ReflectionMethod(CoachRepository::class, 'fetchCoachMatchStats');
        $method->setAccessible(true);

        /** @var array{matchCount:int,wins:int,draws:int,losses:int,goalsFor:int,goalsAgainst:int,cleanSheets:int,debutMatch:?string,lastMatch:?string} $stats */
        $stats = $method->invoke($this, $personId);

        return $stats;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}

$repository = new TestCoachRepository(new CoachMatchStatsTestEntityManager(new CoachMatchStatsTestConnection()));
$stats = $repository->fetchCoachMatchStatsForTest(42);

assertSameValue(6, $stats['matchCount'], 'matchCount');
assertSameValue(2, $stats['wins'], 'wins');
assertSameValue(2, $stats['draws'], 'draws');
assertSameValue(2, $stats['losses'], 'losses');
assertSameValue(8, $stats['goalsFor'], 'goalsFor');
assertSameValue(9, $stats['goalsAgainst'], 'goalsAgainst');
assertSameValue(1, $stats['cleanSheets'], 'cleanSheets');
assertSameValue('2001-01-01', $stats['debutMatch'], 'debutMatch');
assertSameValue('2006-06-06', $stats['lastMatch'], 'lastMatch');

echo "Coach repository match stats test: OK\n";
