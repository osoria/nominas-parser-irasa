<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración: Añade el campo dni a la tabla empleado y lo popula con los NIF/DNI extraídos del PDF de Julio
 */
final class Version20260731000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade campo dni a empleado y popula con datos del PDF de Julio';
    }

    public function up(Schema $schema): void
    {
        // La columna puede existir ya si el esquema se sincronizó previamente desde la entidad
        $columns = $this->connection->getSchemaManager()->listTableColumns('empleado');
        if (!isset($columns['dni'])) {
            $this->addSql('ALTER TABLE empleado ADD dni VARCHAR(20) DEFAULT NULL');
        }

        $empleados = [
            ['44600196M', 'ALEMAN AGUILAR', 'MIRIAM'],
            ['21794612L', 'ARCOS ALBIADES', 'LAURA MARCELA'],
            ['Y0010608X', 'ARHIP', 'CRISTIAN MIHAI'],
            ['25446448F', 'ARMENDARIZ BARA', 'MARIA CARMEN'],
            ['X6580727W', 'BUSTO GONZALEZ', 'VANESA'],
            ['73022062Y', 'CANTON HERNANDEZ', 'MARIA ROCIO'],
            ['29110027P', 'CERDA CEAMANOS', 'CAROLINA BEATRIZ'],
            ['X8862123Q', 'COSTACHE', 'BIANCA'],
            ['Z1770037P', 'CORREDOR GONZALEZ', 'HELEN TATIANA'],
            ['X3581650K', 'DE SOUSA', 'DANIELA'],
            ['X4334320D', 'DIEZ RUBIO', 'FCO JAVIER'],
            ['12338553L', 'DOMINGUEZ GARCIA', 'RICARDO'],
            ['17765780M', 'GALLARIN RODRIGUEZ', 'JOSE LUIS'],
            ['X6437114D', 'GARCIA-CRUZ MINGO', 'LORENA'],
            ['73156306E', 'GIMENO ROYO', 'VICTOR FRANCISCO'],
            ['52963164N', 'GOMEZ LANUZA', 'SALMA'],
            ['60458091S', 'GOMEZ RODRIGUEZ', 'ALEXANDRA'],
            ['36932205R', 'GONZALEZ CRIADO', 'FRANCISCO'],
            ['Y0019089W', 'GRIGORE', 'ALEXANDRA LUIZA'],
            ['26309174A', 'GRIGORE', 'STEFAN'],
            ['44256691E', 'HERNANDEZ QUIROS', 'ROSARIO ENCAR'],
            ['Y2775123B', 'L VIRGILI', 'MARC'],
            ['Y2496184W', 'LAVADO MENDEZ', 'JOSE'],
            ['76922004E', 'LOPEZ VIÑAS', 'JOSE ANTONIO'],
            ['43168308B', 'MANUEL SANGHEZ', 'NADIA'],
            ['X1473033B', 'MATEO SANCHEZ', 'ANA'],
            ['18039959B', 'MURILLO BORRAJO', 'JOSE FCO.'],
            ['X9846252R', 'NAVARRO PEREZ', 'AIDA'],
            ['72964345L', 'PASCUAL MORENO', 'LUIS MIGUEL'],
            ['Y5051951T', 'PEREZ DANIELA', 'GABRIELA'],
            ['17446465E', 'PEREZ MARTINEZ', 'Mª CRISTINA'],
            ['25164590Z', 'SORIA MARCO', 'LUIS MIGUEL'],
            ['26309174A', 'STEFAN GRIGORE', 'ALEJANDRA LUCIA'],
            ['73229781N', 'SUPERVIA FERNANDEZ', 'CYNTHIA'],
            ['Y3768897Q', 'VILCU', 'IRINA ALICE'],
            ['25425665Q', 'VIÑAS SORIA', 'MARIA DEL CARMEN'],
            ['29088015F', 'YUGUERO CASTAÑOSA', 'JOSE MIGUEL'],
        ];

        foreach ($empleados as [$dni, $apellidos, $nombre]) {
            $this->addSql(
                'UPDATE empleado SET dni = :dni WHERE apellidos = :apellidos AND nombre = :nombre',
                ['dni' => $dni, 'apellidos' => $apellidos, 'nombre' => $nombre]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empleado DROP dni');
    }
}