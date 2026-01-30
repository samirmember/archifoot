<?php

namespace App\Controller\Admin;

use App\Entity\NameAlias;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class NameAliasCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NameAlias::class;
    }
}
