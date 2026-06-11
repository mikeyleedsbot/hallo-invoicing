<?php
/**
 * Importeert een MySQL dump in de database.
 * De dump zelf bevat CREATE DATABASE + USE statements, dus die regelt de databasenaam.
 * Wij zorgen alleen dat de oude database eerst weg is.
 *
 * Gebruik: php import-old-dump.php goforitsit_invoice.sql
 */

// Lees .env voor database credentials
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if (!$line || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        switch ($key) {
            case 'DB_HOST': $host = $val; break;
            case 'DB_PORT': $port = (int)$val; break;
            case 'DB_USERNAME': $user = $val; break;
            case 'DB_PASSWORD': $pass = $val; break;
        }
    }
}

$dumpFile = $argv[1] ?? 'goforitsit_invoice.sql';

if (!file_exists($dumpFile)) {
    echo "❌ Dump bestand niet gevonden: $dumpFile\n";
    exit(1);
}

// Verbind met MySQL
$mysqli = new mysqli($host, $user, $pass, '', $port);
if ($mysqli->connect_error) {
    echo "❌ MySQL verbinding mislukt: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ MySQL verbinding OK ($host:$port)\n";

// Verwijder oude database als die bestaat (dump maakt zelf een nieuwe aan)
$mysqli->query("DROP DATABASE IF EXISTS goforitsit_invoice");
echo "🗑️  Oude database verwijderd (indien aanwezig)\n";

// Stel character set in
$mysqli->set_charset("utf8mb4");

// Lees de dump
echo "📥 SQL dump inlezen...\n";
$sql = file_get_contents($dumpFile);
$fileSize = round(strlen($sql) / 1024 / 1024, 1);
echo "   Bestandsgrootte: {$fileSize} MB\n";

// Voer de volledige dump uit via multi_query
// De dump bevat CREATE DATABASE + USE statements die de juiste database aanmaken
echo "📥 SQL statements uitvoeren (dit duurt ~30-60 seconden)...\n";

if ($mysqli->multi_query($sql)) {
    $resultCount = 0;
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        $resultCount++;
        if ($resultCount % 1000 === 0) {
            echo "   $resultCount resultaten verwerkt...\n";
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->errno) {
        echo "⚠️  MySQL fout bij verwerking: " . $mysqli->error . "\n";
        echo "   (sommige fouten zijn normaal bij import)\n";
    }

    echo "✅ $resultCount resultaten verwerkt\n";
} else {
    echo "❌ Multi-query mislukt: " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}

// Verificatie: check of tabellen bestaan in goforitsit_invoice
$mysqli->select_db("goforitsit_invoice");
$result = $mysqli->query("SHOW TABLES");
if (!$result) {
    echo "❌ Database goforitsit_invoice niet gevonden na import!\n";
    $mysqli->close();
    exit(1);
}

$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}
echo "\n📊 Tabellen in goforitsit_invoice: " . count($tables) . "\n";
foreach ($tables as $table) {
    $countResult = $mysqli->query("SELECT COUNT(*) FROM `$table`");
    $count = $countResult ? $countResult->fetch_row()[0] : '?';
    echo "   - $table: $count rijen\n";
}

$mysqli->close();

echo "\n✅ Import compleet!\n";
exit(0);
