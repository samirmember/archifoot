<?php

namespace App\Controller\Admin;

use App\Entity\Fixture;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class FixtureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Fixture::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Match')
            ->setEntityLabelInPlural('Matchs')
            ->setDefaultSort(['matchDate' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $createComplete = Action::new('createComplete', 'Créer un match complet')
            ->linkToRoute('admin_fixture_new_complete')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, $createComplete);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('matchDate', 'Date'),
            AssociationField::new('season', 'Saison'),
            AssociationField::new('stadium', 'Stade'),
            AssociationField::new('country', 'Pays'),
            BooleanField::new('isOfficial', 'Officiel'),
        ];
    }
}
