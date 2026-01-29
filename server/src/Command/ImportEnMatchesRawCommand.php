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
    name: 'app:import:en-matches-raw',
    description: "Importe l'Excel 'EN seniors Matches' dans la table en_match_raw (staging)."
)]
class ImportEnMatchesRawCommand extends Command
{
    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin du xlsx (dans le conteneur)')
            ->addArgument('sheet', InputArgument::OPTIONAL, 'Nom de la feuille', "Matches de l'EN ");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string)$input->getArgument('file');
        $sheetName = (string)$input->getArgument('sheet');

        if (!is_file($file)) {
            $output->writeln("<error>Fichier introuvable : $file</error>");
            return Command::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            $output->writeln("<error>Feuille '$sheetName' introuvable</error>");
            return Command::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) {
            $output->writeln("<error>Feuille vide</error>");
            return Command::FAILURE;
        }

        $headers = array_map(fn($v) => trim((string)$v), $rows[1]);

        $sql = "INSERT INTO en_match_raw
(match_no, category, competition, annee, is_official, stade, ville, pays_stade, match_date_raw, pays_a, buts_a, buts_b, pays_b, bulles, raw_hash)
VALUES
(:match_no, :category, :competition, :annee, :is_official, :stade, :ville, :pays_stade, :match_date_raw, :pays_a, :buts_a, :buts_b, :pays_b, :bulles, :raw_hash)
ON DUPLICATE KEY UPDATE raw_hash = raw_hash";

        $inserted = 0;
        $ignored = 0;

        $this->db->beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                if ($i === 1) {
                    continue;
                }

                $matchNo = $this->toInt($this->getByHeader($headers, $row, 'N° du match'));
                if ($matchNo === null) {
                    continue;
                }

                $data = [
                    'match_no' => $matchNo,
                    'category' => $this->toStr($this->getByHeader($headers, $row, 'Catégorie')),
                    'competition' => $this->toStr($this->getByHeader($headers, $row, 'Compétition')),
                    'annee' => $this->toInt($this->getByHeader($headers, $row, 'Année')),
                    'is_official' => $this->toStr($this->getByHeader($headers, $row, 'matches officiels')),
                    'stade' => $this->toStr($this->getByHeader($headers, $row, 'Stade')),
                    'ville' => $this->toStr($this->getByHeader($headers, $row, 'Ville  -Stade')),
                    'pays_stade' => $this->toStr($this->getByHeader($headers, $row, 'Pays -Stade')),
                    'match_date_raw' => $this->toStr($this->getByHeader($headers, $row, 'Date')),
                    'pays_a' => $this->toStr($this->getByHeader($headers, $row, 'Pays A')),
                    'buts_a' => $this->toInt($this->getByHeader($headers, $row, 'buts A')),
                    'buts_b' => $this->toInt($this->getByHeader($headers, $row, 'Buts B')),
                    'pays_b' => $this->toStr($this->getByHeader($headers, $row, 'Pays B')),
                    'bulles' => $this->toStr($this->getByHeader($headers, $row, 'Bulles')),
                ];

                $data['raw_hash'] = sha1(json_encode($data, JSON_UNESCAPED_UNICODE));

                $affected = $this->db->executeStatement($sql, $data);

                if ($affected > 0) {
                    $inserted++;
                } else {
                    $ignored++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $output->writeln("<error>Erreur import : {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Import OK. inserted=$inserted ignored=$ignored</info>");
        return Command::SUCCESS;
    }

    private function getByHeader(array $headers, array $row, string $headerName): mixed
    {
        foreach ($headers as $colLetter => $header) {
            if ($header === $headerName) {
                return $row[$colLetter] ?? null;
            }
        }
        return null;
    }

    private function toStr(mixed $v): ?string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null) return null;
        if (is_numeric($v)) return (int)$v;

        $s = trim((string)$v);
        if ($s === '') return null;
        if (!preg_match('/^-?\d+$/', $s)) return null;
        return (int)$s;
    }
}
