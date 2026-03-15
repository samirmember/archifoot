<?php

namespace App\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fixture:set-officials-matchs',
    description: 'Met à jour fixture.is_official selon le type de compétition.'
)]
class SetOfficialsMatchsCommand extends Command
{
    private const NON_OFFICIAL_COMPETITIONS = ['Match Amical', 'Tournoi'];

    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->db->beginTransaction();

        try {
            $unofficialUpdatedRows = $this->db->executeStatement(
                <<<'SQL'
                UPDATE fixture f
                SET f.is_official = 0
                WHERE EXISTS (
                    SELECT 1
                    FROM fixture_competition fc
                    INNER JOIN competition c ON c.id = fc.competition_id
                    WHERE fc.fixture_id = f.id
                      AND c.name IN (:competitionNames)
                )
                SQL,
                ['competitionNames' => self::NON_OFFICIAL_COMPETITIONS],
                ['competitionNames' => ArrayParameterType::STRING]
            );

            $officialUpdatedRows = $this->db->executeStatement(
                <<<'SQL'
                UPDATE fixture f
                SET f.is_official = 1
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM fixture_competition fc
                    INNER JOIN competition c ON c.id = fc.competition_id
                    WHERE fc.fixture_id = f.id
                      AND c.name IN (:competitionNames)
                )
                SQL,
                ['competitionNames' => self::NON_OFFICIAL_COMPETITIONS],
                ['competitionNames' => ArrayParameterType::STRING]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $output->writeln(sprintf('<error>Erreur: %s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Terminé: matchs non officiels mis à jour=%d, matchs officiels mis à jour=%d</info>',
            $unofficialUpdatedRows,
            $officialUpdatedRows
        ));

        return Command::SUCCESS;
    }
}
