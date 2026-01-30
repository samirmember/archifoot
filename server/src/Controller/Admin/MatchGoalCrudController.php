<?php

namespace App\Controller\Admin;

use App\Entity\MatchGoal;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MatchGoalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MatchGoal::class;
    }
}
