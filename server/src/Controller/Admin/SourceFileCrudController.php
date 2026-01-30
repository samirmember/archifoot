<?php

namespace App\Controller\Admin;

use App\Entity\SourceFile;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SourceFileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SourceFile::class;
    }
}
