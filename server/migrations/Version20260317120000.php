<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coach information fields for deaths, career, statistics, and biography.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on MySQL/MariaDB.'
        );

        $this->addSql('ALTER TABLE coach ADD death_city_id INT DEFAULT NULL, ADD death_region_id INT DEFAULT NULL, ADD death_country_id INT DEFAULT NULL, ADD career VARCHAR(255) DEFAULT NULL, ADD main_clubs JSON DEFAULT NULL, ADD algeria_player_caps INT DEFAULT NULL, ADD foreign_player_caps INT DEFAULT NULL, ADD head_matches INT DEFAULT NULL, ADD assistant_matches INT DEFAULT NULL, ADD callups INT DEFAULT NULL, ADD bio LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE coach ADD CONSTRAINT FK_4B6B9FE2A24D5E9B FOREIGN KEY (death_city_id) REFERENCES city (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE coach ADD CONSTRAINT FK_4B6B9FE2BE035435 FOREIGN KEY (death_region_id) REFERENCES region (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE coach ADD CONSTRAINT FK_4B6B9FE261F3FC4C FOREIGN KEY (death_country_id) REFERENCES country (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4B6B9FE2A24D5E9B ON coach (death_city_id)');
        $this->addSql('CREATE INDEX IDX_4B6B9FE2BE035435 ON coach (death_region_id)');
        $this->addSql('CREATE INDEX IDX_4B6B9FE261F3FC4C ON coach (death_country_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on MySQL/MariaDB.'
        );

        $this->addSql('ALTER TABLE coach DROP FOREIGN KEY FK_4B6B9FE2A24D5E9B');
        $this->addSql('ALTER TABLE coach DROP FOREIGN KEY FK_4B6B9FE2BE035435');
        $this->addSql('ALTER TABLE coach DROP FOREIGN KEY FK_4B6B9FE261F3FC4C');
        $this->addSql('DROP INDEX IDX_4B6B9FE2A24D5E9B ON coach');
        $this->addSql('DROP INDEX IDX_4B6B9FE2BE035435 ON coach');
        $this->addSql('DROP INDEX IDX_4B6B9FE261F3FC4C ON coach');
        $this->addSql('ALTER TABLE coach DROP death_city_id, DROP death_region_id, DROP death_country_id, DROP career, DROP main_clubs, DROP algeria_player_caps, DROP foreign_player_caps, DROP head_matches, DROP assistant_matches, DROP callups, DROP bio');
    }
}
