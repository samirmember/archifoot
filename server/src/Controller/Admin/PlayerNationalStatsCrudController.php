<?php

namespace App\Controller\Admin;

use App\Entity\PlayerNationalStats;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PlayerNationalStatsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PlayerNationalStats::class;
    }
}
