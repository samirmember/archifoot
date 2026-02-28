<?php

namespace App\Filter;

use App\Entity\Country;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

final class PersonNationalityCountryFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName = 'personNationalityCountry', string $label = 'Nationalité'): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('value_type_options.class', Country::class)
            ->setFormTypeOption('value_type_options.choice_label', 'name')
            ->setFormTypeOption('value_type_options.attr', ['data-ea-widget' => 'ea-autocomplete'])
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $entityAlias = $filterDataDto->getEntityAlias();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $value = $filterDataDto->getValue();
        $personAlias = 'personNationality_' . $parameterName;

        $queryBuilder->leftJoin(sprintf('%s.person', $entityAlias), $personAlias);

        if (null === $value) {
            $queryBuilder->andWhere(sprintf('%s.nationalityCountry %s', $personAlias, $comparison));

            return;
        }

        $orX = new Orx();
        $orX->add(sprintf('%s.nationalityCountry %s (:%s)', $personAlias, $comparison, $parameterName));

        if (ComparisonType::NEQ === $comparison) {
            $orX->add(sprintf('%s.nationalityCountry IS NULL', $personAlias));
        }

        $queryBuilder
            ->andWhere($orX)
            ->setParameter($parameterName, $value);
    }
}
