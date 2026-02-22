<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

/**
 * Public landing page for external applicants.
 * 
 * This page serves as the main entry point for
 * non-authenticated users.
 */
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Karriere - ATS</title>

        <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="card">
                <h1 class="page-title">Karriere</h1>

                <p>
                    Willkommen auf unserer Karriereseite. 
                    Hier finden Sie alle aktuell ausgeschriebenen Stellen.
                </p>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/jobs/index.php" class="btn btn-primary">
                        Offene Stellen ansehen
                    </a>
                </div>

                <hr>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/login.php" class="btn btn-secondary">
                        Mitarbeiter-Login
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>