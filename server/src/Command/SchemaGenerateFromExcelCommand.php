<?php

namespace App\Command;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:schema:generate-from-excel',
    description: 'Génère un SQL MariaDB à partir de Projet.xlsx (feuille "Tables").'
)]
class SchemaGenerateFromExcelCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin vers le fichier Excel')
            ->addArgument('sheet', InputArgument::OPTIONAL, 'Nom de la feuille', 'Tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getArgument('file');
        $sheetName = (string) $input->getArgument('sheet');

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

        $highestRow = (int) $sheet->getHighestRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        /**
         * 1) Détection des tables (ligne 1)
         * Chaque cellule non vide = début d'une table (colonne de départ)
         */
        $tables = []; // [tableName => startCol]
        for ($col = 1; $col <= $highestColumn; $col++) {
            $value = $this->normalizeIdentifier((string) $sheet->getCell([$col, 1])->getValue());
            if ($value !== '') {
                // Si doublon dans la ligne 1, on ne veut pas écraser
                $name = $value;
                $i = 2;
                while (isset($tables[$name])) {
                    $name = $value . '_' . $i;
                    $i++;
                }
                $tables[$name] = $col;
            }
        }

        if (empty($tables)) {
            $output->writeln("<error>Aucune table détectée ligne 1</error>");
            return Command::FAILURE;
        }

        /**
         * 2) Lecture des colonnes pour chaque table
         * Format attendu par table :
         *  - Col startCol     : nom de colonne
         *  - Col startCol + 1 : type (Excel)
         *  - Col startCol + 2 : commentaire
         */
        $schema = []; // [table => [['name'=>..,'type'=>..,'comment'=>..], ...]]
        foreach ($tables as $tableName => $startCol) {
            $tableName = $this->normalizeIdentifier($tableName);
            if ($tableName === '') {
                continue;
            }

            $columns = [];

            for ($row = 2; $row <= $highestRow; $row++) {
                $colName = $this->normalizeIdentifier((string) $sheet->getCell([$startCol, $row])->getValue());
                if ($colName === '') {
                    continue;
                }

                $type = trim((string) $sheet->getCell([$startCol + 1, $row])->getValue());
                $comment = trim((string) $sheet->getCell([$startCol + 2, $row])->getValue());

                $columns[] = [
                    'name'    => $colName,
                    'type'    => $type,
                    'comment' => $comment,
                ];
            }

            if (!empty($columns)) {
                $schema[$tableName] = $columns;
            }
        }

        if (empty($schema)) {
            $output->writeln("<error>Aucune colonne détectée (vérifie la feuille et la structure)</error>");
            return Command::FAILURE;
        }

        /**
         * 3) Génération SQL
         */
        $sql = [];
        $sql[] = "SET NAMES utf8mb4;";
        $sql[] = "SET FOREIGN_KEY_CHECKS=0;";
        $sql[] = "";

        foreach ($schema as $table => $columns) {
            $defs = [];
            $tableChecks = []; // contraintes CHECK au niveau table (évite soucis d'ordre avec COMMENT)
            $pk = null;

            foreach ($columns as $column) {
                $colName = $this->normalizeIdentifier($column['name']);
                $excelType = trim((string) $column['type']);
                $comment = (string) $column['comment'];

                $colDef = $this->mapType($colName, $excelType);

                // Définition de colonne : ordre stable MariaDB
                // `col` TYPE [NOT NULL|NULL] [AUTO_INCREMENT] [DEFAULT ...] COMMENT '...'
                $line = sprintf(
                    "  `%s` %s%s",
                    $colName,
                    $colDef['sqlType'],
                    $colDef['nullability']
                );

                if ($colName === 'id') {
                    $pk = 'id';
                    if ($colDef['autoIncrement'] === true && !str_contains($line, 'AUTO_INCREMENT')) {
                        $line .= ' AUTO_INCREMENT';
                    }
                }

                if ($colDef['default'] !== null) {
                    $line .= ' DEFAULT ' . $colDef['default'];
                }

                // CHECK -> on le met au niveau table, pas inline (évite "CHECK ... COMMENT ..." qui casse)
                if ($colDef['checkExpr'] !== null) {
                    $chkName = $this->makeConstraintName('chk', $table, $colName);
                    $tableChecks[$chkName] = sprintf(
                        "  CONSTRAINT `%s` CHECK (%s)",
                        $chkName,
                        $colDef['checkExpr']
                    );
                }

                if (trim($comment) !== '') {
                    $line .= " COMMENT " . $this->quote($comment);
                }

                $defs[] = $line;
            }

            if ($pk !== null) {
                $defs[] = "  PRIMARY KEY (`$pk`)";
            }

            // Ajoute les CHECK table-level à la fin, avant la fermeture
            foreach ($tableChecks as $chkLine) {
                $defs[] = $chkLine;
            }

            $sql[] = "DROP TABLE IF EXISTS `$table`;";
            $sql[] = "CREATE TABLE `$table` (";
            $sql[] = implode(",\n", $defs);
            $sql[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $sql[] = "";
        }

        $sql[] = "SET FOREIGN_KEY_CHECKS=1;";

        $outputFile = 'var/schema/generated.sql';
        @mkdir(dirname($outputFile), 0777, true);
        file_put_contents($outputFile, implode("\n", $sql));

        $output->writeln("<info>SQL généré : $outputFile</info>");

        return Command::SUCCESS;
    }

    /**
     * Mapping types Excel → MariaDB (robuste)
     *
     * Retour :
     * - sqlType       : ex "INT", "VARCHAR(50)", "DECIMAL(10,2)"
     * - nullability   : " NOT NULL" / " NULL"
     * - autoIncrement : bool
     * - default       : string|null (déjà prêt pour extensions)
     * - checkExpr     : string|null (expression SQL à mettre dans CHECK(...))
     */
    private function mapType(string $column, string $excelType): array
    {
        $t = strtolower(trim($excelType));

        // Nullability : on garde la règle actuelle : id NOT NULL, le reste NULL
        $nullability = ($column === 'id') ? ' NOT NULL' : ' NULL';

        // Par défaut
        $res = [
            'sqlType'       => 'TEXT',
            'nullability'   => $nullability,
            'autoIncrement' => ($column === 'id'),
            'default'       => null,
            'checkExpr'     => null,
        ];

        if ($t === '') {
            return $res;
        }

        // bool / enum(0,1) -> tinyint(1) + CHECK
        if ($t === 'bool' || $t === 'boolean' || $t === 'tinyint(1)' || preg_match('/^enum\s*\(\s*0\s*,\s*1\s*\)$/', $t)) {
            $res['sqlType'] = 'TINYINT';
            $res['checkExpr'] = sprintf("`%s` IN (0,1)", $column);
            return $res;
        }

        // int / integer / tinyint / smallint / bigint
        if (in_array($t, ['int', 'integer'], true)) {
            $res['sqlType'] = 'INT';
            return $res;
        }
        if ($t === 'tinyint') {
            $res['sqlType'] = 'TINYINT';
            return $res;
        }
        if ($t === 'smallint') {
            $res['sqlType'] = 'SMALLINT';
            return $res;
        }
        if ($t === 'bigint') {
            $res['sqlType'] = 'BIGINT';
            return $res;
        }

        // varchar(n), char(n)
        if (preg_match('/^varchar\((\d+)\)$/', $t, $m)) {
            $n = max(1, (int) $m[1]);
            $res['sqlType'] = 'VARCHAR(' . $n . ')';
            return $res;
        }
        if (preg_match('/^char\((\d+)\)$/', $t, $m)) {
            $n = max(1, (int) $m[1]);
            $res['sqlType'] = 'CHAR(' . $n . ')';
            return $res;
        }

        // text variants
        if (in_array($t, ['text', 'tinytext', 'mediumtext', 'longtext'], true)) {
            $res['sqlType'] = strtoupper($t);
            return $res;
        }

        // date / datetime / timestamp / time
        if ($t === 'date') {
            $res['sqlType'] = 'DATE';
            return $res;
        }
        if ($t === 'datetime') {
            $res['sqlType'] = 'DATETIME';
            return $res;
        }
        if ($t === 'timestamp') {
            $res['sqlType'] = 'TIMESTAMP';
            return $res;
        }
        if ($t === 'time') {
            $res['sqlType'] = 'TIME';
            return $res;
        }

        // decimal(p,s)
        if (preg_match('/^decimal\((\d+)\s*,\s*(\d+)\)$/', $t, $m)) {
            $p = max(1, (int) $m[1]);
            $s = max(0, (int) $m[2]);
            if ($s > $p) {
                $s = $p;
            }
            $res['sqlType'] = "DECIMAL($p,$s)";
            return $res;
        }

        // float / double
        if ($t === 'float') {
            $res['sqlType'] = 'FLOAT';
            return $res;
        }
        if ($t === 'double') {
            $res['sqlType'] = 'DOUBLE';
            return $res;
        }

        // json (MariaDB supporte JSON via alias LONGTEXT + validation selon versions/config ; on reste simple)
        if ($t === 'json') {
            $res['sqlType'] = 'JSON';
            return $res;
        }

        // Si on reçoit déjà un type SQL plausible (ex: "varchar(150)", "int unsigned", etc.)
        // On laisse passer prudemment en uppercase, en filtrant les caractères dangereux.
        if ($this->looksLikeSqlType($t)) {
            $res['sqlType'] = strtoupper($t);
            return $res;
        }

        return $res;
    }

    private function looksLikeSqlType(string $t): bool
    {
        // Autorise lettres, chiffres, espaces, parenthèses, virgules, underscores
        // Refuse guillemets, backticks, point-virgule, etc.
        return (bool) preg_match('/^[a-z0-9_ ]+(\([0-9 ,]+\))?$/i', $t);
    }

    private function normalizeIdentifier(string $value): string
    {
        $v = trim($value);
        // Optionnel : tu peux ajouter ici des règles (remplacer espaces par _ etc.)
        return $v;
    }

    private function makeConstraintName(string $prefix, string $table, string $column): string
    {
        // MariaDB/MySQL: max 64 chars pour un identifiant
        $base = $prefix . '_' . $table . '_' . $column;
        $base = preg_replace('/[^a-zA-Z0-9_]+/', '_', $base) ?? $base;
        $base = trim($base, '_');

        if (strlen($base) <= 64) {
            return $base;
        }

        // Troncature + petit hash pour éviter collisions
        $hash = substr(sha1($base), 0, 8);
        $truncated = substr($base, 0, 64 - 1 - strlen($hash));
        return rtrim($truncated, '_') . '_' . $hash;
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
