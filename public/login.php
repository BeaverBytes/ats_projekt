<?php
declare(strict_types=1);

/**
 * Login entry point for internal users (admin, recruiter).
 */
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';

// Ensure session is active
startSession();

// Redirect already authenticated users
if (isAuthenticated()) {
    $role = currentUserRole();

    if ($role === 'admin') {
        header('Location: ' . BASE_PATH . '/admin/dashboard.php');
        exit;
    }

    header('Location: ' . BASE_PATH . '/recruiter/dashboard.php');
    exit;
}

$error = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string)($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (login($email, $password)) {

        $role = currentUserRole();

        // Role-based redirect after successful login
        if ($role === 'admin') {
            header('Location: ' . BASE_PATH . '/admin/dashboard.php');
            exit;
        }

        header('Location: ' . BASE_PATH . '/recruiter/dashboard.php');
        exit;
    }

    // Generic error message to prevent user enumeration
    $error = 'Login fehlgeschlagen. Bitte E-Mail und Passwort prüfen.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login – ATS</title>

    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
</head>
<body>
    <h1>Login</h1>

    <?php if ($error): ?>
        <!-- Escape output to prevent XSS -->
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="email">E-Mail</label><br>
            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="username"
                value="<?php 
                    // Wiederbefüllung des Email-Feldes bei Fehler zur erhöhung der Usability
                    echo htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <div style="margin-top: 8px;">
            <label for="password">Passwort</label><br>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </div>

        <div style="margin-top: 12px;">
            <button type="submit">Anmelden</button>
        </div>
    </form>

    <div style="margin-top: 20px;">
        <a href="<?= BASE_PATH ?>/index.php">← Zur Karriereseite</a>
    </div>
</body>
</html>