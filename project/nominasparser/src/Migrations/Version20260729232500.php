<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729232500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade columna ultimo_mes_envio a la tabla empleado';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empleado ADD ultimo_mes_envio INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empleado DROP ultimo_mes_envio');
    }
}