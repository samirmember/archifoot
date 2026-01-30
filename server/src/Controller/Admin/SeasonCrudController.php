<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SeasonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Season::class;
    }
}
