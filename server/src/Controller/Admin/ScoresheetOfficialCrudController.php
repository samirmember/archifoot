<?php

namespace App\Controller\Admin;

use App\Entity\ScoresheetOfficial;
use App\Filter\PersonNationalityCountryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ScoresheetOfficialCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ScoresheetOfficial::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setSearchFields(['person.fullName', 'nameText', 'role']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(PersonNationalityCountryFilter::new('nationality', 'Nationalité'));
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            return [
                AssociationField::new('scoresheet', 'Feuille de match'),
                TextField::new('personFullName', 'Personne'),
                TextField::new('personNationalityCountryName', 'Nationalité'),
                TextField::new('role', 'Rôle'),
            ];
        }

        return [
            AssociationField::new('scoresheet', 'Feuille de match'),
            AssociationField::new('person', 'Personne')->autocomplete(),
            TextField::new('nameText', 'Nom libre')->setRequired(false),
            TextField::new('role', 'Rôle')->setRequired(false),
        ];
    }
}
