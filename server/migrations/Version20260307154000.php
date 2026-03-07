<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external_number on person, coach and player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person ADD external_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE coach ADD external_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD external_number VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP external_number');
        $this->addSql('ALTER TABLE coach DROP external_number');
        $this->addSql('ALTER TABLE person DROP external_number');
    }
}
