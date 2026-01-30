<?php

namespace App\Controller\Admin;

use App\Entity\TrophyAward;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TrophyAwardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TrophyAward::class;
    }
}
