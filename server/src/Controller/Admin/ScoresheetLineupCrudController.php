<?php

namespace App\Controller\Admin;

use App\Entity\ScoresheetLineup;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ScoresheetLineupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ScoresheetLineup::class;
    }
}
