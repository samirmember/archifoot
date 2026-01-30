<?php

namespace App\Controller\Admin;

use App\Entity\Matchday;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MatchdayCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Matchday::class;
    }
}
