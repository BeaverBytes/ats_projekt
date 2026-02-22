<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/jobs.php';

/**
 * Public job detail page.
 * 
 * Validates the job ID from user input and ensures
 * that only active jobs are displayed.
 */


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

        <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="form-actions">
                <a href="<?= BASE_PATH ?>/jobs/index.php" class="btn btn-secondary">← Zur Stellenliste</a>
            </div>

            <div class="card">
                <h1 class="page-title"><?= htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8') ?></h1>

                <?php if (!empty($job['location'])): ?>
                    <p><strong>Ort:</strong> <?= htmlspecialchars((string)$job['location'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif ?>

                <hr>

                <h2>Beschreibung</h2>
                <p class="job-description"><?= htmlspecialchars((string)$job['description'], ENT_QUOTES, 'UTF-8') ?></p>

                <hr>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/apply.php?job_id=<?= (int)$job['job_id'] ?>" class="btn btn-primary">
                        Jetzt bewerben
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>