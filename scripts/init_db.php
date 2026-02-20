<?php
/** scripts/init_db.php 
* Initialisiert die Datenbankstruktur anhand der schema.sql
*/
declare(strict_types=1);

// Script darf nur über die Kommandozeile ausgeführt werden
// Verhindert Web-Zugriff
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

// Zentrale DB-Verbindungsfunktion einbinden
require __DIR__ . '/../src/db.php';

try {
    // Konfigurierte PDO-Instanz (Singleton) abrufen
    $pdo = getDatabaseConnection();
    
    // Schema-Datei einlesen
    $schemaFile = __DIR__ . '/../data/schema.sql';
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException("Schema nicht lesbar: $schemaFile");
    }

    // SQL-Statements ausführen
    $pdo->exec($sql);

    echo "Datenbank initialisiert.\n";

} catch (Throwable $e) {
    // Fehlerausgabe im CLI-Kontext ausgeben und mit Exit-Code 1 beenden
    echo "FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}
