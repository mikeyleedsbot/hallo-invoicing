#!/usr/bin/env php
<?php
/**
 * Database dump script - gebruikt PDO in plaats van mysqldump
 * Genereert een volledige SQL dump van de hallo_invoicing database
 */

$host     = '127.0.0.1';
$port     = 3306;
$dbname   = 'hallo_invoicing';
$username = 'root';
$password = '';

$timestamp = date('Ymd_His');
$filename  = __DIR__ . "/hallo_invoicing_dump_{$timestamp}.sql";

echo "==> Database dump maken van '{$dbname}'...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]
    );
} catch (PDOException $e) {
    echo "❌ Kan niet verbinden met database: " . $e->getMessage() . "\n";
    exit(1);
}

$output = [];
$output[] = "-- Database dump: {$dbname}";
$output[] = "-- Gemaakt op: " . date('Y-m-d H:i:s');
$output[] = "-- Generator: php-dump.php (PDO)";
$output[] = "";
$output[] = "SET FOREIGN_KEY_CHECKS = 0;";
$output[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
$output[] = "SET time_zone = '+00:00';";
$output[] = "SET NAMES utf8mb4;";
$output[] = "";

// Haal alle tabellen op
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$totalTables = count($tables);
echo "==> {$totalTables} tabellen gevonden\n";

foreach ($tables as $i => $table) {
    $num = $i + 1;
    echo "    [{$num}/{$totalTables}] {$table}...\n";

    // DROP TABLE
    $output[] = "-- -------------------------------------------";
    $output[] = "-- Tabel: {$table}";
    $output[] = "-- -------------------------------------------";
    $output[] = "DROP TABLE IF EXISTS `{$table}`;";
    $output[] = "";

    // CREATE TABLE
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $output[] = $create['Create Table'] . ";";
    $output[] = "";

    // INSERT data
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        $columns = array_keys($rows[0]);
        $columnList = implode('`, `', $columns);

        // Batch inserts per 100 rijen voor performance
        $chunks = array_chunk($rows, 100);
        foreach ($chunks as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                $escaped = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $escaped[] = 'NULL';
                    } else {
                        $escaped[] = $pdo->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $escaped) . ')';
            }
            $output[] = "INSERT INTO `{$table}` (`{$columnList}`) VALUES";
            $output[] = implode(",\n", $values) . ";";
            $output[] = "";
        }
    }

    $output[] = "";
}

$output[] = "SET FOREIGN_KEY_CHECKS = 1;";
$output[] = "";

// Schrijf naar bestand
$content = implode("\n", $output);
file_put_contents($filename, $content);

$size = round(filesize($filename) / 1024, 1);
echo "\n✅ Dump gemaakt: " . basename($filename) . " ({$size} KB)\n";
echo "   Locatie: {$filename}\n";
