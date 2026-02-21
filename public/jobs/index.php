<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/jobs.php';

$jobs = listActiveJobs();
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Offene Stellen - ATS</title>
    </head>
    <body>
        <main>
            <h1>Offene Stellen</h1>

            <p><a href="<?= BASE_PATH ?>/index.php"><- Zur Karriereseite</a></p>

            <?php if (count($jobs) === 0): ?>
                <p>Aktuell sind keine Stellen ausgeschrieben</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($jobs as $job): ?>
                        <li>
                            <a href="<?= BASE_PATH ?>/jobs/show.php?id=<?= (int)$job['job_id'] ?>">
                                <?= htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php if (!empty($job['location'])): ?>
                                - <?= htmlspecialchars((string)$job['location'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </main>
    </body>
</html>