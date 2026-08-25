<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración: Pone ultimo_mes_envio = 7 (Julio) a los 21 empleados
 * que ya recibieron su nómina de Julio correctamente.
 */
final class Version20260729233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Actualiza ultimo_mes_envio a 7 (Julio) para empleados que ya recibieron su nómina';
    }

    public function up(Schema $schema): void
    {
        $empleados = [
            ['VIÑAS SORIA', 'MARIA DEL CARMEN'],
            ['GONZALEZ CRIADO', 'FRANCISCO'],
            ['PASCUAL MORENO', 'LUIS MIGUEL'],
            ['ARMENDARIZ BARA', 'MARIA CARMEN'],
            ['CERDA CEAMANOS', 'CAROLINA BEATRIZ'],
            ['SORIA MARCO', 'LUIS MIGUEL'],
            ['GIMENO ROYO', 'VICTOR FRANCISCO'],
            ['LOPEZ VIÑAS', 'JOSE ANTONIO'],
            ['DOMINGUEZ GARCIA', 'RICARDO'],
            ['SUPERVIA FERNANDEZ', 'CYNTHIA'],
            ['COSTACHE', 'BIANCA'],
            ['PEREZ MARTINEZ', 'Mª CRISTINA'],
            ['ARCOS ALBIADES', 'LAURA MARCELA'],
            ['GALLARIN RODRIGUEZ', 'JOSE LUIS'],
            ['CANTON HERNANDEZ', 'MARIA ROCIO'],
            ['VILCU', 'IRINA ALICE'],
            ['GAYARRE RUIZ', 'IDOYA'],
            ['LANA BERNAL', 'ARANZAZU'],
            ['MILLAS CARNICER', 'PILAR'],
            ['MILLAS CARNICER', 'ADRIANA'],
            ['DOVYDAITYTE', 'BRIGITA'],
        ];

        foreach ($empleados as [$apellidos, $nombre]) {
            $this->addSql(
                "UPDATE empleado SET ultimo_mes_envio = 7 WHERE apellidos = :apellidos AND nombre = :nombre",
                ['apellidos' => $apellidos, 'nombre' => $nombre]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE empleado SET ultimo_mes_envio = NULL WHERE ultimo_mes_envio = 7");
    }
}