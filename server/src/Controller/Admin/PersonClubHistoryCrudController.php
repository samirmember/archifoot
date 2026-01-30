<?php

namespace App\Controller\Admin;

use App\Entity\PersonClubHistory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PersonClubHistoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PersonClubHistory::class;
    }
}
