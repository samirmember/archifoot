<?php

namespace App\Controller\Admin;

use App\Entity\NationalTeam;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class NationalTeamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NationalTeam::class;
    }
}
