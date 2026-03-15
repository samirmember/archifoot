<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping {
    #[\Attribute(\Attribute::TARGET_CLASS)] class Entity { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_CLASS)] class Table { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)] class Index { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)] class UniqueConstraint { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class Id { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class GeneratedValue { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class Column { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class ManyToOne { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class ManyToMany { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class OneToMany { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class JoinColumn { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class JoinTable { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class InverseJoinColumn { public function __construct(...$args) {} }
}

namespace ApiPlatform\Metadata {
    #[\Attribute(\Attribute::TARGET_CLASS)] class ApiResource { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)] class ApiFilter { public function __construct(...$args) {} }
    class Get { public function __construct(...$args) {} }
    class GetCollection { public function __construct(...$args) {} }
}

namespace ApiPlatform\Doctrine\Orm\Filter {
    class SearchFilter {}
}

namespace Symfony\Component\Serializer\Attribute {
    #[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)] class Groups { public function __construct(...$args) {} }
    #[\Attribute(\Attribute::TARGET_PROPERTY)] class Ignore { public function __construct(...$args) {} }
}

namespace Doctrine\Common\Collections {
    interface Collection extends \IteratorAggregate {
        public function add(mixed $element): bool;
        public function removeElement(mixed $element): bool;
        public function contains(mixed $element): bool;
        public function map(callable $func): self;
        public function filter(callable $func): self;
        public function getValues(): array;
    }

    class ArrayCollection implements Collection {
        /** @var array<int,mixed> */
        private array $elements;

        public function __construct(array $elements = []) { $this->elements = array_values($elements); }
        public function add(mixed $element): bool { $this->elements[] = $element; return true; }
        public function removeElement(mixed $element): bool {
            foreach ($this->elements as $i => $candidate) {
                if ($candidate === $element) { unset($this->elements[$i]); $this->elements = array_values($this->elements); return true; }
            }
            return false;
        }
        public function contains(mixed $element): bool {
            foreach ($this->elements as $candidate) {
                if ($candidate === $element) { return true; }
            }
            return false;
        }
        public function map(callable $func): Collection { return new self(array_map($func, $this->elements)); }
        public function filter(callable $func): Collection { return new self(array_values(array_filter($this->elements, $func))); }
        public function getValues(): array { return array_values($this->elements); }
        public function getIterator(): \Traversable { return new \ArrayIterator($this->elements); }
    }
}

namespace App\Entity {
    interface Collection extends \Doctrine\Common\Collections\Collection {}
    class ArrayCollection extends \Doctrine\Common\Collections\ArrayCollection implements Collection {}
}

namespace {
    require_once __DIR__ . '/../src/Entity/Country.php';
    require_once __DIR__ . '/../src/Entity/Category.php';
    require_once __DIR__ . '/../src/Entity/NationalTeam.php';
    require_once __DIR__ . '/../src/Entity/City.php';
    require_once __DIR__ . '/../src/Entity/Region.php';
    require_once __DIR__ . '/../src/Entity/Club.php';
    require_once __DIR__ . '/../src/Entity/Team.php';
    require_once __DIR__ . '/../src/Entity/Competition.php';
    require_once __DIR__ . '/../src/Entity/Season.php';
    require_once __DIR__ . '/../src/Entity/Division.php';
    require_once __DIR__ . '/../src/Entity/Edition.php';
    require_once __DIR__ . '/../src/Entity/Stage.php';
    require_once __DIR__ . '/../src/Entity/Stadium.php';
    require_once __DIR__ . '/../src/Entity/Matchday.php';
    require_once __DIR__ . '/../src/Entity/Fixture.php';
    require_once __DIR__ . '/../src/Entity/FixtureParticipant.php';

    use App\Entity\Competition;
    use App\Entity\Country;
    use App\Entity\Edition;
    use App\Entity\Fixture;
    use App\Entity\FixtureParticipant;
    use App\Entity\NationalTeam;
    use App\Entity\Season;
    use App\Entity\Stage;
    use App\Entity\Team;

    function setId(object $entity, int $id): void {
        $ref = new ReflectionClass($entity);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($entity, $id);
    }

    function assertSame(mixed $expected, mixed $actual, string $label): void {
        if ($expected !== $actual) {
            fwrite(STDERR, "Assertion failed for {$label}.\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
            exit(1);
        }
    }

    $worldCup = new Competition();
    setId($worldCup, 5);
    $worldCup->setName('Coupe du Monde');

    $can = new Competition();
    setId($can, 2);
    $can->setName("Coupe d'Afrique des Nations (CAN)");

    $editionWorldCup = new Edition();
    setId($editionWorldCup, 2);
    $editionWorldCup->setName('2006 en Allemagne');
    $editionWorldCup->setCompetition($worldCup);

    $editionCan = new Edition();
    setId($editionCan, 6);
    $editionCan->setName('2006 en Égypte');
    $editionCan->setCompetition($can);

    $stage1 = new Stage();
    setId($stage1, 1);
    $stage1->setName('Eliminatoire');
    $stage1->setEdition($editionWorldCup);

    $stage2 = new Stage();
    setId($stage2, 6);
    $stage2->setName('Eliminatoire');
    $stage2->setEdition($editionCan);

    $dz = new Country();
    setId($dz, 25);
    $dz->setName('Algérie');
    $dz->setIso2('DZ');
    $dz->setIso3('DZA');

    $fr = new Country();
    setId($fr, 12);
    $fr->setName('France');
    $fr->setIso2('FR');
    $fr->setIso3('FRA');

    $ntA = new NationalTeam();
    $ntA->setName('Algérie');
    $ntA->setCountry($dz);

    $ntB = new NationalTeam();
    $ntB->setName('France');
    $ntB->setCountry($fr);

    $teamA = new Team();
    setId($teamA, 25);
    $teamA->setNationalTeam($ntA);

    $teamB = new Team();
    setId($teamB, 12);
    $teamB->setNationalTeam($ntB);

    $participantA = new FixtureParticipant();
    $participantA->setRole('A');
    $participantA->setTeam($teamA);
    $participantA->setScore(0);

    $participantB = new FixtureParticipant();
    $participantB->setRole('B');
    $participantB->setTeam($teamB);
    $participantB->setScore(3);

    $season = new Season();
    $season->setName('2004');

    $fixture = new Fixture();
    setId($fixture, 455);
    $fixture->setExternalMatchNo(455);
    $fixture->setMatchDate(new DateTimeImmutable('2004-06-05T00:00:00+00:00'));
    $fixture->setPlayed(true);
    $fixture->setSeason($season);
    $fixture->setCountry($dz);
    $fixture->addStageWithConsistency($stage1);
    $fixture->addStageWithConsistency($stage2);

    $participantsProp = new ReflectionProperty($fixture, 'participants');
    $participantsProp->setAccessible(true);
    $participantsProp->setValue($fixture, new Doctrine\Common\Collections\ArrayCollection([$participantA, $participantB]));

    $stages = $fixture->getStagesData();
    assertSame(2, count($stages), 'stages count');
    assertSame(1, $stages[0]['id'], 'stage 1 id');
    assertSame('2006 en Allemagne', $stages[0]['edition']['name'], 'edition 1 name');
    assertSame('Coupe du Monde', $stages[0]['edition']['competition']['name'], 'competition 1 name');
    assertSame(6, $stages[1]['id'], 'stage 2 id');
    assertSame("Coupe d'Afrique des Nations (CAN)", $stages[1]['edition']['competition']['name'], 'competition 2 name');

    assertSame(['id' => 25, 'name' => 'Algérie', 'iso2' => 'DZ'], $fixture->getTeamA(), 'teamA');
    assertSame(['id' => 12, 'name' => 'France', 'iso2' => 'FR'], $fixture->getTeamB(), 'teamB');
    assertSame(0, $fixture->getScoreA(), 'scoreA');
    assertSame(3, $fixture->getScoreB(), 'scoreB');
    assertSame('Algérie', $fixture->getCountryStadiumName(), 'countryStadiumName');

    echo "Fixture response shape test: OK\n";
}
