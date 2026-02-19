<?php

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../data/ats.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Datenbankverbindung erfolgreich!";
} catch (PDOException $e) {
    echo "Fehler bei der Verbindung: " . $e->getMessage();
}