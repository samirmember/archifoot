<?php

namespace App\Controller\Admin;

use App\Entity\Referee;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RefereeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Referee::class;
    }
}
