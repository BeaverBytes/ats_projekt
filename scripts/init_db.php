<?php
declare(strict_types=1);

/**
 * Initializes the database schema from schema.sql.
 *
 * CLI-only script to prevent accidental execution via web.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

// Zentrale DB-Verbindungsfunktion einbinden
require __DIR__ . '/../src/db.php';

try {
    // Obtain configured PDO connection
    $pdo = getDatabaseConnection();
    
    // Load schema definition
    $schemaFile = __DIR__ . '/../data/schema.sql';
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException("Schema nicht lesbar: $schemaFile");
    }

    // Execute schema statements
    $pdo->exec($sql);

    echo "Datenbank initialisiert.\n";

} catch (Throwable $e) {
    // Output error in CLI context and exit with non-zero status
    echo "FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}
