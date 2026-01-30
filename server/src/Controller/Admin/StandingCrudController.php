<?php

namespace App\Controller\Admin;

use App\Entity\Standing;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class StandingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Standing::class;
    }
}
