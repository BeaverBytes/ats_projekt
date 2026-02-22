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
        <main class="container">
            <div class="card">
                <h1 class="page-title">Login</h1>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">

                    <div class="form-group">
                        <label for="email">E-Mail</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            autocomplete="username"
                            value="<?= htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Passwort</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Anmelden
                        </button>
                    </div>

                </form>

                <hr>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/index.php" class="btn btn-secondary">
                        ← Zur Karriereseite
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>