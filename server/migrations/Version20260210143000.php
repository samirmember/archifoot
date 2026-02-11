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
            ['RB', 'Arrière droit'],
            ['LB', 'Arrière gauche'],
            ['CB', 'Défenseur central'],
            ['RWB', 'Piston droit'],
            ['LWB', 'Piston gauche'],
            ['CDM', 'Milieu défensif'],
            ['CM', 'Milieu central'],
            ['CAM', 'Milieu offensif'],
            ['RM', 'Milieu droit'],
            ['LM', 'Milieu gauche'],
            ['RW', 'Ailier droit'],
            ['LW', 'Ailier gauche'],
            ['CF', 'Attaquant de soutien'],
            ['ST', 'Avant-centre'],
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
        $codes = ['GK', 'RB', 'LB', 'CB', 'RWB', 'LWB', 'CDM', 'CM', 'CAM', 'RM', 'LM', 'RW', 'LW', 'CF', 'ST'];

        foreach ($codes as $code) {
            $this->addSql('DELETE FROM position WHERE code = :code', ['code' => $code]);
        }
    }
}
