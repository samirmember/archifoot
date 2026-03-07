<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import:external-numbers',
    description: 'Importe les numéros externes depuis un fichier Excel pour les entraîneurs ou les joueurs.'
)]
class ImportExternalNumbersCommand extends Command
{
    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('target', InputArgument::REQUIRED, 'Type d\'import: coach|player')
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier xlsx')
            ->addArgument('sheet', InputArgument::OPTIONAL, 'Nom de la feuille (par défaut: première feuille)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = mb_strtolower(trim((string) $input->getArgument('target')));
        $file = (string) $input->getArgument('file');
        $sheetName = $input->getArgument('sheet');

        if (!\in_array($target, ['coach', 'player'], true)) {
            $output->writeln('<error>Target invalide. Valeurs possibles: coach|player</error>');
            return Command::FAILURE;
        }

        if (!is_file($file)) {
            $output->writeln("<error>Fichier introuvable: {$file}</error>");
            return Command::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $sheetName === null
            ? $spreadsheet->getSheet(0)
            : $spreadsheet->getSheetByName((string) $sheetName);

        if ($sheet === null) {
            $output->writeln('<error>Feuille introuvable.</error>');
            return Command::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) {
            $output->writeln('<error>La feuille Excel ne contient pas de données.</error>');
            return Command::FAILURE;
        }

        $headers = array_map(static fn ($value): string => trim((string) $value), $rows[1]);
        $nameColumn = $target === 'coach' ? 'Entraineur' : 'Joueur';

        if ($this->findColumnLetter($headers, 'Num') === null || $this->findColumnLetter($headers, $nameColumn) === null) {
            $output->writeln(sprintf('<error>Colonnes obligatoires manquantes. Colonnes attendues: Num + %s</error>', $nameColumn));
            return Command::FAILURE;
        }

        $stats = ['updated' => 0, 'missing' => 0, 'ignored' => 0];

        $this->db->beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index === 1) {
                    continue;
                }

                $externalNumber = $this->toStr($this->getByHeader($headers, $row, 'Num'));
                $fullName = $this->toStr($this->getByHeader($headers, $row, $nameColumn));

                if ($externalNumber === null || $fullName === null) {
                    $stats['ignored']++;
                    continue;
                }

                $updated = $target === 'coach'
                    ? $this->updateCoachNumber($fullName, $externalNumber)
                    : $this->updatePlayerNumber($fullName, $externalNumber);

                if ($updated === 0) {
                    $stats['missing']++;
                } else {
                    $stats['updated'] += $updated;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $output->writeln('<error>Erreur import: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Import terminé (%s): updated=%d missing=%d ignored=%d</info>',
            $target,
            $stats['updated'],
            $stats['missing'],
            $stats['ignored']
        ));

        return Command::SUCCESS;
    }

    private function updateCoachNumber(string $fullName, string $externalNumber): int
    {
        return $this->db->executeStatement(
            <<<'SQL'
                UPDATE coach c
                INNER JOIN person p ON p.id = c.person_id
                SET p.external_number = :externalNumber,
                    p.photo_url = CONCAT(:externalNumber, '.webp'),
                    c.external_number = :externalNumber
                WHERE LOWER(TRIM(p.full_name)) = LOWER(TRIM(:fullName))
            SQL,
            [
                'externalNumber' => $externalNumber,
                'fullName' => $fullName,
            ]
        );
    }

    private function updatePlayerNumber(string $fullName, string $externalNumber): int
    {
        return $this->db->executeStatement(
            <<<'SQL'
                UPDATE player pl
                INNER JOIN person p ON p.id = pl.person_id
                SET p.external_number = :externalNumber,
                    p.photo_url = CONCAT(:externalNumber, '.webp'),
                    pl.external_number = :externalNumber
                WHERE LOWER(TRIM(p.full_name)) = LOWER(TRIM(:fullName))
            SQL,
            [
                'externalNumber' => $externalNumber,
                'fullName' => $fullName,
            ]
        );
    }

    private function getByHeader(array $headers, array $row, string $headerName): mixed
    {
        $column = $this->findColumnLetter($headers, $headerName);

        return $column === null ? null : ($row[$column] ?? null);
    }

    private function findColumnLetter(array $headers, string $headerName): ?string
    {
        foreach ($headers as $column => $headerValue) {
            if (mb_strtolower($headerValue) === mb_strtolower($headerName)) {
                return (string) $column;
            }
        }

        return null;
    }

    private function toStr(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
