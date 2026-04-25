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

    $dbPath = __DIR__ . '/../data/ats.sqlite';

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,            // Throw on DB errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return query results as associative arrays
        PDO::ATTR_EMULATE_PREPARES => false,                    // Use real prepared statements
    ]);

    // SQLite has FK support but disables it per connection by default
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}
