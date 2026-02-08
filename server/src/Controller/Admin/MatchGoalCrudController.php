<?php

namespace App\Controller\Admin;

use App\Entity\Fixture;
use App\Entity\MatchGoal;
use App\Entity\Player;
use App\Entity\Team;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MatchGoalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MatchGoal::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $goalTypeField = ChoiceField::new('goalType', 'goal_type')->setChoices([
            'Normal' => 'normal',
            'Pénalty' => 'penalty',
            'But CSC' => 'own_goal',
        ]);

        if (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            return [
                AssociationField::new('fixture')
                    ->setFormTypeOption('choice_label', static fn (Fixture $fixture): string => sprintf('Match #%d', $fixture->getId() ?? 0)),
                AssociationField::new('team')
                    ->setFormTypeOption('choice_label', static fn (Team $team): string => $team->getDisplayName() ?: sprintf('Team #%d', $team->getId() ?? 0)),
                AssociationField::new('scorer')
                    ->setFormTypeOption('choice_label', static fn (Player $player): string => $player->getPerson()?->getFullName() ?: sprintf('Player #%d', $player->getId() ?? 0)),
                TextField::new('scorerText'),
                TextField::new('minute'),
                $goalTypeField,
            ];
        }

        return [
            IdField::new('id'),
            IntegerField::new('fixture.id', 'fixture_id'),
            TextField::new('team.displayName', 'team'),
            TextField::new('scorer.person.fullName', 'scorer'),
            TextField::new('scorerText'),
            TextField::new('minute'),
            $goalTypeField,
        ];
    }
}
