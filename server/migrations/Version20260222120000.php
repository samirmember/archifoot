<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align schema with latest role/person_assignment/scoresheet_staff model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fixture ADD COLUMN IF NOT EXISTS internal_notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD COLUMN IF NOT EXISTS photo_url VARCHAR(150) DEFAULT NULL');

        $this->addSql('ALTER TABLE referee DROP FOREIGN KEY IF EXISTS fk_referee_country_id');
        $this->addSql('ALTER TABLE referee DROP COLUMN IF EXISTS country_id');

        $this->addSql('ALTER TABLE scoresheet DROP FOREIGN KEY IF EXISTS fk_scoresheet_coach_id');
        $this->addSql('ALTER TABLE scoresheet DROP COLUMN IF EXISTS coach_id');
        $this->addSql('ALTER TABLE scoresheet CHANGE COLUMN form_state status VARCHAR(1) DEFAULT NULL');

        $this->addSql("CREATE TABLE IF NOT EXISTS role (
            id INT AUTO_INCREMENT NOT NULL,
            type VARCHAR(20) NOT NULL,
            code VARCHAR(30) NOT NULL,
            label VARCHAR(80) NOT NULL,
            UNIQUE INDEX uq_role_code (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE IF NOT EXISTS person_assignment (
            id INT AUTO_INCREMENT NOT NULL,
            person_id INT NOT NULL,
            team_id INT NOT NULL,
            role_id INT NOT NULL,
            season_id INT DEFAULT NULL,
            from_date DATE DEFAULT NULL,
            to_date DATE DEFAULT NULL,
            INDEX ix_tpr_role_dates (team_id, role_id, from_date, to_date),
            INDEX ix_tpr_person_dates (person_id, from_date, to_date),
            INDEX ix_tpr_season (season_id),
            UNIQUE INDEX uq_tpr_exact (person_id, team_id, role_id, season_id, from_date, to_date),
            PRIMARY KEY(id),
            CONSTRAINT fk_pa_person_id FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE,
            CONSTRAINT fk_pa_team_id FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE,
            CONSTRAINT fk_pa_role_id FOREIGN KEY (role_id) REFERENCES role (id),
            CONSTRAINT fk_pa_season_id FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE IF NOT EXISTS scoresheet_staff (
            id INT AUTO_INCREMENT NOT NULL,
            scoresheet_id INT DEFAULT NULL,
            team_id INT DEFAULT NULL,
            person_id INT DEFAULT NULL,
            role VARCHAR(32) DEFAULT NULL,
            INDEX idx_ss_scoresheet_id (scoresheet_id),
            INDEX idx_ss_team_id (team_id),
            INDEX idx_ss_person_id (person_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_scoresheet_staff_scoresheet_id FOREIGN KEY (scoresheet_id) REFERENCES scoresheet (id) ON DELETE CASCADE,
            CONSTRAINT fk_scoresheet_staff_team_id FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE SET NULL,
            CONSTRAINT fk_scoresheet_staff_person_id FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('DROP TABLE IF EXISTS player_team_membership');

        $roles = [
            ['PLAYER', 'PLAYER', 'Joueur'],
            ['COACH', 'HEAD_COACH', 'Entraineur principal'],
            ['COACH', 'ASSISTANT_COACH', 'Entraineur adjoint'],
            ['REFEREE', 'REFEREE', 'Arbitre'],
        ];

        foreach ($roles as [$type, $code, $label]) {
            $this->addSql(
                'INSERT INTO role (type, code, label)
                 SELECT :type, :code, :label
                 WHERE NOT EXISTS (SELECT 1 FROM role WHERE code = :code)',
                ['type' => $type, 'code' => $code, 'label' => $label],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_team_membership (id INT AUTO_INCREMENT NOT NULL, player_id INT NOT NULL, team_id INT NOT NULL, from_date DATE DEFAULT NULL, to_date DATE DEFAULT NULL, is_current TINYINT(1) DEFAULT 0 NOT NULL, source_note VARCHAR(200) DEFAULT NULL, INDEX ix_pcm_player_current (player_id, is_current), INDEX ix_pcm_player_period (player_id, from_date, to_date), INDEX ix_pcm_team_period (team_id, from_date, to_date), UNIQUE INDEX uq_pcm_exact (player_id, team_id, from_date, to_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE IF EXISTS scoresheet_staff');
        $this->addSql('DROP TABLE IF EXISTS person_assignment');
        $this->addSql('DROP TABLE IF EXISTS role');
        $this->addSql('ALTER TABLE scoresheet CHANGE COLUMN status form_state VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE scoresheet ADD COLUMN coach_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE scoresheet ADD CONSTRAINT fk_scoresheet_coach_id FOREIGN KEY (coach_id) REFERENCES person (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE referee ADD COLUMN country_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE referee ADD CONSTRAINT fk_referee_country_id FOREIGN KEY (country_id) REFERENCES country (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE person DROP COLUMN photo_url');
        $this->addSql('ALTER TABLE fixture DROP COLUMN internal_notes');
    }
}
