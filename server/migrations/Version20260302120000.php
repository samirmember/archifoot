<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feature_photo_url column to person table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person ADD feature_photo_url VARCHAR(150) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person DROP feature_photo_url');
    }
}
