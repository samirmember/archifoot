<?php

namespace App\Controller\Admin;

use App\Entity\TrophyAwardPerson;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TrophyAwardPersonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TrophyAwardPerson::class;
    }
}
