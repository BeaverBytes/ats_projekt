<?php
declare(strict_types=1);

/**
 * Admin dashboard.
 *
 * Access restricted to users with role "admin".
 */
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';

// Enforce admin role
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Dashboard – ATS</title>

        <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="card">
                <h1 class="page-title">Admin Dashboard</h1>

                <p>Du bist eingeloggt (admin).</p>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </main>
    </body>
</html>