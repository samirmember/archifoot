<?php

namespace App\Controller\Admin;

use App\Entity\Division;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class DivisionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Division::class;
    }
}
