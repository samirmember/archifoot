<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\Country;
use App\Entity\Stadium;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class StadiumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Stadium::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('city', 'city_id')
                ->setFormTypeOption('choice_label', static fn (City $city): string => $city->getName() ?: sprintf('City #%d', $city->getId() ?? 0)),
            AssociationField::new('country', 'country_id')
                ->setFormTypeOption('choice_label', static fn (Country $country): string => $country->getName() ?: sprintf('Country #%d', $country->getId() ?? 0)),
            IntegerField::new('capacity'),
        ];
    }
}
