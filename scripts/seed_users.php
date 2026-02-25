<?php
declare(strict_types=1);

/**
 * Seeds initial admin and recruiter accounts.
 *
 * CLI-only script. Safe to run multiple times (idempotent).
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

// Include central database connection
require_once __DIR__ . '/../src/db.php';

try {
    $pdo = getDatabaseConnection();

    // Default development users
    $users = [
        [
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin'
        ],
        [
            'email' => 'recruiter1@example.com',
            'password' => 'recruiter123',
            'role' => 'recruiter'
        ],
        [
            'email' => 'recruiter2@example.com',
            'password' => 'recruiter123',
            'role' => 'recruiter'
        ],
    ];

    foreach ($users as $user) {

        // Check if user already exists
        $stmt = $pdo->prepare(
            "SELECT user_id FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $user['email']]);

        if ($stmt->fetch()) {
            echo ucfirst($user['role']) . " already exists: {$user['email']}\n";
            continue;
        }

        // Securely hash password before storing
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);

        // Insert user into database
        $insertStmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, role)
            VALUES (:email, :password_hash, :role)
        ");

        $insertStmt->execute([
            'email' => $user['email'],
            'password_hash' => $hashedPassword,
            'role' => $user['role']
        ]);

        echo ucfirst($user['role']) . " successfully created.\n";
        echo "Email: {$user['email']}\n";
        echo "Password: {$user['password']}\n";
        echo "-----------------------------\n";
    }

    echo "Seeding finished.\n";

} catch (PDOException $e) {
    // Basic error handling
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}