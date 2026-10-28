<?php

namespace App\Controller\Admin;

use App\Entity\Empleado;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EmpleadoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Empleado::class;
    }
}
