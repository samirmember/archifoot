<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add photo_url column to coach table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coach ADD COLUMN IF NOT EXISTS photo_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coach DROP COLUMN IF EXISTS photo_url');
    }
}
