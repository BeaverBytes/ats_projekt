<?php

/** src/db.php
 * Liefert eine konfigurierte PDO-Verbindung zur SQLite-Datenbank.
 * Die Verbindung wird pro Request einmalig erzeugt (Singleton).
 */
declare(strict_types=1);

// PDO-Instanz wird zwischengespeichert (Singleton pro Request)
function getDatabaseConnection(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }    

    // Pfad zur SQLite-Datenbankdatei
    $dbPath = __DIR__ . '/../data/ats.sqlite';

    // Aufbau der PDO-Verbindung mit definierten Attributen
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,            // Fehler werden als Exception geworfen
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Assoziative Arrays bei fetch()
        PDO::ATTR_EMULATE_PREPARES => false,                    // Nutzung echter Prepared Statements
    ]);

    // Foreign-Key-Constraints Aktivierung
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}
