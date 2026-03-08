<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate position table with FIFA standard football positions';
    }

    public function up(Schema $schema): void
    {
        $positions = [
            ['GK', 'Gardien de but'],
            ['DEF', 'Défenseur'],
            ['MID', 'Milieu de terrain'],
            ['ATT', 'Attaquant'],
        ];

        foreach ($positions as [$code, $label]) {
            $this->addSql(
                'INSERT INTO position (code, label)
                 SELECT :code, :label
                 WHERE NOT EXISTS (SELECT 1 FROM position WHERE code = :code)',
                ['code' => $code, 'label' => $label],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $codes = ['GK', 'DEF', 'MID', 'ATT'];

        foreach ($codes as $code) {
            $this->addSql('DELETE FROM position WHERE code = :code', ['code' => $code]);
        }
    }
}
