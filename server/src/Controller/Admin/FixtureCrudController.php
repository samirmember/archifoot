<?php

namespace App\Controller\Admin;

use App\Entity\Fixture;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class FixtureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Fixture::class;
    }
}
