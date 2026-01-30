<?php

namespace App\Controller\Admin;

use App\Entity\MatchCard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MatchCardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MatchCard::class;
    }
}
