<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración: Sustituye el campo ultimo_mes_envio (INT) por periodo (VARCHAR)
 * para poder distinguir la paga extra de la nómina ordinaria aunque coincidan
 * en el mismo mes.
 *
 * A los empleados que ya tenían ultimo_mes_envio = 8 (Agosto) se les asigna el
 * periodo "MENS 01 AGO 26 a 31 AGO 26".
 */
final class Version20260831000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sustituye ultimo_mes_envio por periodo en la tabla empleado';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empleado ADD periodo VARCHAR(100) DEFAULT NULL');
        $this->addSql("UPDATE empleado SET periodo = 'MENS 01 AGO 26 a 31 AGO 26' WHERE ultimo_mes_envio = 8");
        $this->addSql('ALTER TABLE empleado DROP ultimo_mes_envio');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empleado ADD ultimo_mes_envio INT DEFAULT NULL');
        $this->addSql("UPDATE empleado SET ultimo_mes_envio = 8 WHERE periodo = 'MENS 01 AGO 26 a 31 AGO 26'");
        $this->addSql('ALTER TABLE empleado DROP periodo');
    }
}
