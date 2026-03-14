<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fixture:update-outcomes-algeria',
    description: 'Met à jour fixture_participant.outcome pour les fixtures de 2 participants, relatif à la team_id=1 (Algérie).',
)]
class UpdateFixtureOutcomeForAlgeriaCommand extends Command
{
    private const ALGERIA_TEAM_ID = 1;
    private const OUTCOME_LOSER = 0;
    private const OUTCOME_WINNER = 1;
    private const OUTCOME_DRAW = 2;

    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fixtures = $this->db->fetchAllAssociative(<<<'SQL'
            SELECT fp.fixture_id
            FROM fixture_participant fp
            GROUP BY fp.fixture_id
            HAVING COUNT(*) = 2
               AND SUM(CASE WHEN fp.team_id = :algeriaTeamId THEN 1 ELSE 0 END) = 1
            SQL,
            ['algeriaTeamId' => self::ALGERIA_TEAM_ID]
        );

        $processedFixtures = 0;
        $updatedRows = 0;
        $skippedFixtures = 0;

        $this->db->beginTransaction();

        try {
            foreach ($fixtures as $fixtureRow) {
                $fixtureId = (int) $fixtureRow['fixture_id'];

                $participants = $this->db->fetchAllAssociative(
                    'SELECT id, team_id, score FROM fixture_participant WHERE fixture_id = :fixtureId ORDER BY id ASC',
                    ['fixtureId' => $fixtureId]
                );

                if (count($participants) !== 2) {
                    $skippedFixtures++;
                    continue;
                }

                $algeria = null;
                $opponent = null;

                foreach ($participants as $participant) {
                    if ((int) $participant['team_id'] === self::ALGERIA_TEAM_ID) {
                        $algeria = $participant;
                    } else {
                        $opponent = $participant;
                    }
                }

                if ($algeria === null || $opponent === null || $algeria['score'] === null || $opponent['score'] === null) {
                    $skippedFixtures++;
                    continue;
                }

                $algeriaScore = (int) $algeria['score'];
                $opponentScore = (int) $opponent['score'];

                if ($algeriaScore > $opponentScore) {
                    $algeriaOutcome = self::OUTCOME_WINNER;
                    $opponentOutcome = self::OUTCOME_LOSER;
                } elseif ($algeriaScore < $opponentScore) {
                    $algeriaOutcome = self::OUTCOME_LOSER;
                    $opponentOutcome = self::OUTCOME_WINNER;
                } else {
                    $algeriaOutcome = self::OUTCOME_DRAW;
                    $opponentOutcome = self::OUTCOME_DRAW;
                }

                // Handling special cases 
                if (in_array($fixtureId, [
                    258,273,335,422,602
                ])) {
                    switch ($fixtureId) {
                        case 258:
                            // @Todo: Attente de réponse de Selhani
                        case 273:
                            $algeriaOutcome = self::OUTCOME_LOSER;
                            $opponentOutcome = self::OUTCOME_WINNER;
                        case 335:
                            // @Todo: Attente de réponse de Selhani
                        case 422:
                            // @Todo: Attente de réponse de Selhani
                        case 602:
                            $algeriaOutcome = self::OUTCOME_WINNER;
                            $opponentOutcome = self::OUTCOME_LOSER;
                    }

                }

                $updatedRows += $this->db->executeStatement(
                    'UPDATE fixture_participant SET outcome = :outcome WHERE id = :id',
                    ['outcome' => $algeriaOutcome, 'id' => (int) $algeria['id']]
                );

                $updatedRows += $this->db->executeStatement(
                    'UPDATE fixture_participant SET outcome = :outcome WHERE id = :id',
                    ['outcome' => $opponentOutcome, 'id' => (int) $opponent['id']]
                );

                $processedFixtures++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $output->writeln(sprintf('<error>Erreur: %s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Terminé: fixtures traitées=%d, fixtures ignorées=%d, lignes mises à jour=%d</info>',
            $processedFixtures,
            $skippedFixtures,
            $updatedRows
        ));

        return Command::SUCCESS;
    }
}
