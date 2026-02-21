<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/jobs.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    echo "Job nicht gefunden.";
    exit;
}

$job = findActiveJobById($id);
if ($job === null) {
    http_response_code(404);
    echo "Job nicht gefunden.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8') ?> - ATS</title>
    </head>
    <body>
        <main>
            <p><a href="index.php">Zur Stellenliste</a></p>

            <h1><?= htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8') ?></h1>

            <?php if (!empty($job['location'])): ?>
                <p><strong>Ort:</strong><?= htmlspecialchars((string)$jobs['location'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>

            <hr>

            <h2>Beschreibung</h2>
            <p style="white-space: pre-wrap;"><?= htmlspecialchars((string)$job['description'], ENT_QUOTES, 'UTF-8') ?></p>

            <hr>

            <p>
                <a href="../apply.php?job_id=<?= (int)$job['job_id'] ?>">Jetzt bewerben</a>
            </p>
        </main>
    </body>
</html>