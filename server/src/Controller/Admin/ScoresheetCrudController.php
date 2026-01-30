<?php

namespace App\Controller\Admin;

use App\Entity\Scoresheet;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ScoresheetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Scoresheet::class;
    }
}
