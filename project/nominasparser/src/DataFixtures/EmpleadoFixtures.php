<?php

namespace App\DataFixtures;

use App\Entity\Empleado;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class EmpleadoFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Carmen'));
        $empleado->setApellidos(strtoupper('Armendariz Bara'));
        $empleado->setEmail('carmen@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Andres'));
        $empleado->setApellidos(strtoupper('Bonifacio Escusol'));
        $empleado->setEmail('boni@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Carolina'));
        $empleado->setApellidos(strtoupper('Cerda Ceamanos'));
        $empleado->setEmail('carolina@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Victor'));
        $empleado->setApellidos(strtoupper('Gimeno Royo'));
        $empleado->setEmail('toreli25@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Francisco'));
        $empleado->setApellidos(strtoupper('Gonzalez Criado'));
        $empleado->setEmail('pacogonzalez@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Antonio'));
        $empleado->setApellidos(strtoupper('Lopez Salas'));
        $empleado->setEmail('antonio@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Luis Miguel'));
        $empleado->setApellidos(strtoupper('Pascual Moreno'));
        $empleado->setEmail('kolalo11@hotmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Luis Miguel'));
        $empleado->setApellidos(strtoupper('Soria Marco'));
        $empleado->setEmail('luis@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Denia'));
        $empleado->setApellidos(strtoupper('Mendoza'));
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Jose Luis'));
        $empleado->setApellidos(strtoupper('Gallarin'));
        $empleado->setEmail('jose_gr88@hotmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Juan Jose'));
        $empleado->setApellidos(strtoupper('Hernandez Quiros'));
        $empleado->setEmail('juanjo.nandez.quiros@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Rocio'));
        $empleado->setApellidos(strtoupper('Jimenez Arroy'));
        $empleado->setEmail('rozio_jimenez@hotmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Abraham'));
        $empleado->setApellidos(strtoupper('Lopez Gil'));
        $empleado->setEmail('abrahamtbe@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Ayla'));
        $empleado->setApellidos(strtoupper('Escudero Lainez'));
        $empleado->setEmail('escuderoayla@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Ines'));
        $empleado->setApellidos(strtoupper('Serrano Laborda'));
        $empleado->setEmail('ines.plw@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Cynthia'));
        $empleado->setApellidos(strtoupper('Supervia Fernandez'));
        $empleado->setEmail('cynthia.ejea@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Beatriz'));
        $empleado->setApellidos(strtoupper('Rived Navarro'));
        $empleado->setEmail('bearived@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Claudia'));
        $empleado->setApellidos(strtoupper('Vicente Fernandez'));
        $empleado->setEmail('claudia_vf9@hotmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Zohra'));
        $empleado->setApellidos(strtoupper('Asoufi'));
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Idoya'));
        $empleado->setApellidos(strtoupper('Gayarre Ruiz'));
        $empleado->setEmail('idoyagayarre@hotmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Arancha'));
        $empleado->setApellidos(strtoupper('Lana Bernal'));
        $empleado->setEmail('aranchalana77@gmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Adriana'));
        $empleado->setApellidos(strtoupper('Millas Carnicer'));
        $empleado->setEmail('adrianaejea17@hotmail.com');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Pilar'));
        $empleado->setApellidos(strtoupper('Millas Carnicer'));
        $empleado->setEmail('pilar@irasasl.es');
        $manager->persist($empleado);

        $empleado = new Empleado();
        $empleado->setNombre(strtoupper('Carla'));
        $empleado->setApellidos(strtoupper('Sanz Dieste'));
        $empleado->setEmail('carlaelbayo@gmail.com');
        $manager->persist($empleado);

        $manager->flush();
    }
}
