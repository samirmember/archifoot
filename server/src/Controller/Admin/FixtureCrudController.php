<?php

namespace App\Controller\Admin;

use App\Entity\Fixture;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class FixtureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Fixture::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $bulkCreateAction = Action::new('fullCreate', 'Nouveau match complet')
            ->linkToRoute('admin_fixture_full_new')
            ->createAsGlobalAction()
            ->setIcon('fa fa-list-check');

        return $actions
            ->add('index', $bulkCreateAction);
    }
}
