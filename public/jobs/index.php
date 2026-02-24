<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/jobs.php';

/**
 * Public job listing page.
 * 
 * Retrieves only active jobs to ensure that 
 * unpublished positions remain inaccessible.
 */

$pdo = getDatabaseConnection();
$jobs = listActiveJobs($pdo);
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Offene Stellen - ATS</title>

         <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="form-actions">
                <a href="<?= BASE_PATH ?>/index.php" class="btn btn-secondary">← Zur Karriereseite</a>
            </div>

            <div class="card">
                <h1 class="page-title">Offene Stellen</h1>

                <?php if (count($jobs) === 0): ?>
                    <p>Aktuell sind keine Stellen ausgeschrieben.</p>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="card">
                            <h2 style="margin-top: 0;">
                                <a href="<?= BASE_PATH ?>/jobs/show.php?id=<?= (int)$job['job_id'] ?>">
                                    <?= htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h2>

                            <?php if (!empty($job['location'])): ?>
                                <p class="muted" style="margin: 0;">
                                    Ort: <?= htmlspecialchars((string)$job['location'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>

                            <div class="form-actions">
                                <a href="<?= BASE_PATH ?>/jobs/show.php?id=<?= (int)$job['job_id'] ?>" class="btn btn-primary">
                                    Details ansehen
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </body>
</html>