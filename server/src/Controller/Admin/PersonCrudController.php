<?php

namespace App\Controller\Admin;

use App\Entity\Person;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PersonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Person::class;
    }
}
