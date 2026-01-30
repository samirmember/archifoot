<?php

namespace App\Controller\Admin;

use App\Entity\ScoresheetOfficial;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ScoresheetOfficialCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ScoresheetOfficial::class;
    }
}
