<?php
declare(strict_types=1);
/** 
 * Returns a configured PDO connection to the SQLite database.
 *
 * The connection is created once per request and reused
 * to ensure consistent configuration across the application.
 */

function getDatabaseConnection(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }    

    // Path to the SQLite database file (stored outside /public)
    $dbPath = __DIR__ . '/../data/ats.sqlite';

    // Create PDO connection with strict error handling
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,            // Throw database errors as exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return query results as associative arrays
        PDO::ATTR_EMULATE_PREPARES => false,                    // Use native prepared statements (disable emulation)
    ]);

    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}
