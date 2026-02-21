<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Karriere - ATS</title>
    </head>
    <body>
        <main>
            <h1>Karriere</h1>
            <p>Willkommen auf unserer Karriereseite. Hier finden Sie alle aktuell ausgeschriebenen Stellen.</p>
            <p><a href="<?= BASE_PATH ?>/jobs/index.php">Offene Stellen ansehen</a></p>

            <hr>

            <p><a href="<?= BASE_PATH ?>/login.php">Mitarbeiter-Login</a></p>
        </main>
    </body>
</html>