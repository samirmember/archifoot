<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class FixtureParticipantCountryIso3OrFilter extends AbstractFilter
{
    private const SINGLE_PROPERTY = 'participants.team.country.iso3';
    private const NATIONAL_PROPERTY = 'participants.team.nationalTeam.country.iso3';
    private const CLUB_PROPERTY = 'participants.team.club.country.iso3';

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (!\in_array($property, [self::SINGLE_PROPERTY, self::NATIONAL_PROPERTY, self::CLUB_PROPERTY], true)) {
            return;
        }

        $filters = $context['filters'] ?? [];

        $globalIso3 = $this->normalizeIso3Value($filters[self::SINGLE_PROPERTY] ?? null);
        $nationalIso3 = $this->normalizeIso3Value($filters[self::NATIONAL_PROPERTY] ?? null);
        $clubIso3 = $this->normalizeIso3Value($filters[self::CLUB_PROPERTY] ?? null);

        if ($globalIso3 !== null) {
            $nationalIso3 ??= $globalIso3;
            $clubIso3 ??= $globalIso3;
        }

        if ($nationalIso3 === null && $clubIso3 === null) {
            return;
        }

        // Évite d'appliquer la même contrainte plusieurs fois quand plusieurs paramètres sont évalués.
        if ($globalIso3 !== null && $property !== self::SINGLE_PROPERTY) {
            return;
        }

        if ($globalIso3 === null && $property === self::CLUB_PROPERTY && $nationalIso3 !== null) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $participantsAlias = $queryNameGenerator->generateJoinAlias('participants');
        $teamAlias = $queryNameGenerator->generateJoinAlias('team');
        $nationalTeamAlias = $queryNameGenerator->generateJoinAlias('nationalTeam');
        $nationalCountryAlias = $queryNameGenerator->generateJoinAlias('nationalCountry');
        $clubAlias = $queryNameGenerator->generateJoinAlias('club');
        $clubCountryAlias = $queryNameGenerator->generateJoinAlias('clubCountry');

        $queryBuilder
            ->leftJoin(sprintf('%s.participants', $rootAlias), $participantsAlias)
            ->leftJoin(sprintf('%s.team', $participantsAlias), $teamAlias)
            ->leftJoin(sprintf('%s.nationalTeam', $teamAlias), $nationalTeamAlias)
            ->leftJoin(sprintf('%s.country', $nationalTeamAlias), $nationalCountryAlias)
            ->leftJoin(sprintf('%s.club', $teamAlias), $clubAlias)
            ->leftJoin(sprintf('%s.country', $clubAlias), $clubCountryAlias);

        if ($nationalIso3 !== null && $clubIso3 !== null) {
            $nationalParameter = $queryNameGenerator->generateParameterName('nationalIso3');
            $clubParameter = $queryNameGenerator->generateParameterName('clubIso3');

            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(sprintf('%s.iso3', $nationalCountryAlias), sprintf(':%s', $nationalParameter)),
                    $queryBuilder->expr()->eq(sprintf('%s.iso3', $clubCountryAlias), sprintf(':%s', $clubParameter))
                ))
                ->setParameter($nationalParameter, $nationalIso3)
                ->setParameter($clubParameter, $clubIso3);

            return;
        }

        if ($nationalIso3 !== null) {
            $nationalParameter = $queryNameGenerator->generateParameterName('nationalIso3');
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq(sprintf('%s.iso3', $nationalCountryAlias), sprintf(':%s', $nationalParameter)))
                ->setParameter($nationalParameter, $nationalIso3);

            return;
        }

        $clubParameter = $queryNameGenerator->generateParameterName('clubIso3');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq(sprintf('%s.iso3', $clubCountryAlias), sprintf(':%s', $clubParameter)))
            ->setParameter($clubParameter, $clubIso3);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::SINGLE_PROPERTY => [
                'property' => self::SINGLE_PROPERTY,
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'ISO3 du pays recherché. Ce filtre est appliqué en OR sur la sélection nationale et sur le club.',
                ],
            ],
            self::NATIONAL_PROPERTY => [
                'property' => self::NATIONAL_PROPERTY,
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Compatibilité: ISO3 du pays de la sélection nationale.',
                ],
            ],
            self::CLUB_PROPERTY => [
                'property' => self::CLUB_PROPERTY,
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Compatibilité: ISO3 du pays du club.',
                ],
            ],
        ];
    }

    private function normalizeIso3Value(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : strtoupper($trimmed);
    }
}
