<?php

namespace App\Controller\Admin;

use App\Entity\ImportBatch;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ImportBatchCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImportBatch::class;
    }
}
