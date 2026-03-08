<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260308150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player selections/goals/main_clubs and person death_date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD selections INT DEFAULT NULL, ADD goals INT DEFAULT NULL, ADD main_clubs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD death_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person DROP death_date');
        $this->addSql('ALTER TABLE player DROP selections, DROP goals, DROP main_clubs');
    }
}
