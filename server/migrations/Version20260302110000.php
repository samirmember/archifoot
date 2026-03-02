<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create person_photo table for multi-photo player galleries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE person_photo (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, image_url VARCHAR(150) NOT NULL, caption VARCHAR(150) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, INDEX IDX_BC9D1BE217BBB47 (person_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE person_photo ADD CONSTRAINT FK_BC9D1BE217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE person_photo');
    }
}

