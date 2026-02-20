<?php
/** 
 * scripts/seed_admin.php
 * Dient der idempotenten Initialisung eines Admin-Accounts für das System.
 */
declare(strict_types=1);

// Skript darf nur über CLI ausgeführt werden
// Verhindert Web-Zugriff
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

// Zentrale DB-Verbindungsfunktion einbinden
require_once __DIR__ . '/../src/db.php';

try {
    $pdo = getDatabaseConnection();

    // Seed-Daten für initialen Administrator
    $adminEmail = 'admin@example.com';
    $adminPasswordPlain = 'admin123';
    $adminRole = 'admin';

    // Idempotenz-Prüfung
    $stmt = $pdo->prepare(
        "SELECT user_id FROM users WHERE email = :email LIMIT 1"
    );
    $stmt->execute(['email' => $adminEmail]);

    if ($stmt->fetch()) {
        echo "Admin user already exists. No action taken.\n";
        exit(0);
    }

    // Passwort hashen
    $hashedPassword = password_hash($adminPasswordPlain, PASSWORD_DEFAULT);

    // Admin-Account anlegen mit Prepared Statement
    $insertStmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, role)
        VALUES (:email, :password_hash, :role)
    ");

    $insertStmt->execute([
        'email' => $adminEmail,
        'password_hash' => $hashedPassword,
        'role' => $adminRole
    ]);

    // Konsolenausgabe bei erfolgreicher Durchführung
    echo "Admin user successfully created.\n";
    echo "Login credentials:\n";
    echo "Email: {$adminEmail}\n";
    echo "Password: {$adminPasswordPlain}";

} catch (PDOException $e) {
    // Fehlerbehandlung
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}