<?php

namespace App\Controller\Admin;

use App\Entity\Trophy;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TrophyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Trophy::class;
    }
}
