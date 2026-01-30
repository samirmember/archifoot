<?php

namespace App\Controller\Admin;

use App\Entity\FixtureParticipant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class FixtureParticipantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FixtureParticipant::class;
    }
}
