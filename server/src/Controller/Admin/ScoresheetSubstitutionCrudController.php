<?php

namespace App\Controller\Admin;

use App\Entity\ScoresheetSubstitution;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ScoresheetSubstitutionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ScoresheetSubstitution::class;
    }
}
