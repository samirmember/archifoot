<?php

namespace App\Controller\Admin;

use App\Entity\MatchGoal;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MatchGoalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MatchGoal::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('fixture'),
            AssociationField::new('team'),
            AssociationField::new('scorer'),
            TextField::new('scorerText'),
            TextField::new('minute'),
            ChoiceField::new('goalType', 'goal_type')->setChoices([
                'Normal' => 'normal',
                'Pénalty' => 'penalty',
                'But CSC' => 'own_goal',
            ]),
        ];
    }
}
