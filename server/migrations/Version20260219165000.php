<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219165000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move player and coach photos to person.photo_url';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person ADD photo_url VARCHAR(500) DEFAULT NULL');

        $this->addSql(
            "UPDATE person p
             INNER JOIN player pl ON pl.person_id = p.id
             SET p.photo_url = pl.photo_url
             WHERE pl.photo_url IS NOT NULL AND pl.photo_url <> ''"
        );

        $this->addSql(
            "UPDATE person p
             INNER JOIN coach c ON c.person_id = p.id
             SET p.photo_url = c.photo_url
             WHERE (p.photo_url IS NULL OR p.photo_url = '')
               AND c.photo_url IS NOT NULL
               AND c.photo_url <> ''"
        );

        $this->addSql('ALTER TABLE player DROP photo_url');
        $this->addSql('ALTER TABLE coach DROP photo_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD photo_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE coach ADD photo_url VARCHAR(500) DEFAULT NULL');

        $this->addSql(
            'UPDATE player pl
             INNER JOIN person p ON pl.person_id = p.id
             SET pl.photo_url = p.photo_url'
        );

        $this->addSql(
            'UPDATE coach c
             INNER JOIN person p ON c.person_id = p.id
             SET c.photo_url = p.photo_url'
        );

        $this->addSql('ALTER TABLE person DROP photo_url');
    }
}
