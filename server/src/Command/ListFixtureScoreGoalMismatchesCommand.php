<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fixture:list-score-goal-mismatches',
    description: 'Liste les rencontres dont le score ne correspond pas au nombre de buts enregistrés (en tenant compte des own_goal).',
)]
class ListFixtureScoreGoalMismatchesCommand extends Command
{
    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $participantRows = $this->db->fetchAllAssociative(<<<'SQL'
            SELECT
                f.id AS fixture_id,
                f.match_date,
                fp.role,
                fp.team_id,
                fp.score,
                t.name AS team_name
            FROM fixture f
            INNER JOIN fixture_participant fp ON fp.fixture_id = f.id
            LEFT JOIN team t ON t.id = fp.team_id
            WHERE fp.score IS NOT NULL
            ORDER BY f.id ASC, fp.role ASC, fp.id ASC
            SQL);

        $fixtures = [];
        foreach ($participantRows as $row) {
            $fixtureId = (int) $row['fixture_id'];

            if (!isset($fixtures[$fixtureId])) {
                $fixtures[$fixtureId] = [
                    'match_date' => $row['match_date'],
                    'participants' => [],
                ];
            }

            $fixtures[$fixtureId]['participants'][] = [
                'role' => $row['role'],
                'team_id' => $row['team_id'] !== null ? (int) $row['team_id'] : null,
                'team_name' => $row['team_name'] ?? sprintf('Team #%s', (string) $row['team_id']),
                'score' => (int) $row['score'],
            ];
        }

        if ($fixtures === []) {
            $output->writeln('<comment>Aucune rencontre avec score renseigné.</comment>');

            return Command::SUCCESS;
        }

        $goalRows = $this->db->fetchAllAssociative(<<<'SQL'
            SELECT fixture_id, team_id, goal_type
            FROM match_goal
            WHERE fixture_id IS NOT NULL
            ORDER BY fixture_id ASC, id ASC
            SQL);

        $goalsByFixture = [];
        foreach ($goalRows as $row) {
            $fixtureId = (int) $row['fixture_id'];
            $goalsByFixture[$fixtureId][] = [
                'team_id' => $row['team_id'] !== null ? (int) $row['team_id'] : null,
                'goal_type' => $row['goal_type'],
            ];
        }

        $mismatches = [];

        foreach ($fixtures as $fixtureId => $fixtureData) {
            $participants = $fixtureData['participants'];
            if (count($participants) !== 2) {
                continue;
            }

            $goalCountByTeam = [];
            foreach ($participants as $participant) {
                if ($participant['team_id'] !== null) {
                    $goalCountByTeam[$participant['team_id']] = 0;
                }
            }

            $fixtureGoals = $goalsByFixture[$fixtureId] ?? [];
            foreach ($fixtureGoals as $goal) {
                $scorerTeamId = $goal['team_id'];
                $isOwnGoal = $goal['goal_type'] === 'own_goal';

                if ($scorerTeamId === null) {
                    continue;
                }

                if ($isOwnGoal) {
                    foreach ($participants as $participant) {
                        $participantTeamId = $participant['team_id'];

                        if ($participantTeamId !== null && $participantTeamId !== $scorerTeamId) {
                            $goalCountByTeam[$participantTeamId] = ($goalCountByTeam[$participantTeamId] ?? 0) + 1;
                            break;
                        }
                    }

                    continue;
                }

                if (array_key_exists($scorerTeamId, $goalCountByTeam)) {
                    $goalCountByTeam[$scorerTeamId]++;
                }
            }

            $hasMismatch = false;
            foreach ($participants as $participant) {
                $teamId = $participant['team_id'];
                $goals = $teamId !== null ? ($goalCountByTeam[$teamId] ?? 0) : 0;

                if ($participant['score'] !== $goals) {
                    $hasMismatch = true;
                    break;
                }
            }

            if (!$hasMismatch) {
                continue;
            }

            $teamA = $participants[0];
            $teamB = $participants[1];

            $mismatches[] = [
                $fixtureId,
                $fixtureData['match_date'] ?? '-',
                sprintf('%s (%s)', $teamA['team_name'], $teamA['role'] ?? '?'),
                sprintf('%d / %d', $teamA['score'], $teamA['team_id'] !== null ? ($goalCountByTeam[$teamA['team_id']] ?? 0) : 0),
                sprintf('%s (%s)', $teamB['team_name'], $teamB['role'] ?? '?'),
                sprintf('%d / %d', $teamB['score'], $teamB['team_id'] !== null ? ($goalCountByTeam[$teamB['team_id']] ?? 0) : 0),
            ];
        }

        if ($mismatches === []) {
            $output->writeln('<info>Aucune incohérence détectée entre scores et buts.</info>');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<comment>%d rencontre(s) incohérente(s) détectée(s).</comment>', count($mismatches)));

        $table = new Table($output);
        $table->setHeaders(['Fixture', 'Date', 'Équipe A (rôle)', 'Score / Buts', 'Équipe B (rôle)', 'Score / Buts']);
        $table->setRows($mismatches);
        $table->render();

        return Command::SUCCESS;
    }
}
