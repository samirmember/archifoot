<?php

namespace App\Controller\Admin;

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
            AssociationField::new('city', 'city_id'),
            AssociationField::new('country', 'country_id'),
            IntegerField::new('capacity'),
        ];
    }
}
