<?php
declare(strict_types=1);

/**
 * Recruiter job overview.
 *
 * Lists jobs created by the current user (including inactive jobs).
 */

require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/jobs.php';

requireAnyRole(['admin', 'recruiter']);

$pdo = getDatabaseConnection();

$userId = currentUserId();
$role = currentUserRole();

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Handle publish toggle via POST and redirect (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $jobId = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $newState = filter_input(INPUT_POST, 'new_state', FILTER_VALIDATE_INT); // expected 0 or 1

    if ($jobId === false || $jobId === null || $jobId < 1) {
        header('Location: ' . BASE_PATH . '/recruiter/jobs/index.php?error=invalid_job');
        exit;
    }

    if ($newState === false || $newState === null || !in_array($newState, [0, 1], true)) {
        header('Location: ' . BASE_PATH . '/recruiter/jobs/index.php?error=invalid_state');
        exit;
    }

    $job = findJobById($pdo, (int)$jobId);
    if ($job === null) {
        header('Location: ' . BASE_PATH . '/recruiter/jobs/index.php?error=not_found');
        exit;
    }

    // Authorization (server-side):
    // Admins may update any job; recruiters only jobs they created.
    if ($role !== 'admin' && (int)$job['created_by_user_id'] !== (int)$userId) {
        header('Location: ' . BASE_PATH . '/recruiter/jobs/index.php?error=forbidden');
        exit;
    }

    setJobActive($pdo, (int)$jobId, (int)$newState);

    header('Location: ' . BASE_PATH . '/recruiter/jobs/index.php?success=updated');
    exit;
}

/**
 * Data for view:
 * Admin -> all jobs
 * Recruiter -> only own jobs
 */
$jobs = ($role === 'admin')
    ? listAllJobs($pdo)
    : listJobsByCreator($pdo, (int)$userId);

$flashSuccess = $_GET['success'] ?? '';
$flashError   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Offene Stellen verwalten - ATS</title>
        <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="card">
                <h1 class="page-title">Ausgeschriebene Stellen</h1>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/recruiter/dashboard.php" class="btn btn-secondary">← Dashboard</a>
                    <a href="<?= BASE_PATH ?>/recruiter/jobs/new.php" class="btn btn-primary">+ Neue Stelle</a>
                    <a href="<?= BASE_PATH ?>/logout.php" class="btn btn-secondary">Logout</a>
                </div>

                <?php if ($flashSuccess === 'updated'): ?>
                    <div class="alert alert-success">Status wurde aktualisiert.</div>
                <?php endif; ?>

                <?php if ($flashError !== ''): ?>
                    <div class="alert alert-error">
                        <?php if ($flashError === 'invalid_job'): ?>
                            Ungültige Stellen-ID.
                        <?php elseif ($flashError === 'invalid_state'): ?>
                            Ungültiger Status.
                        <?php elseif ($flashError === 'not_found'): ?>
                            Stelle nicht gefunden.
                        <?php elseif ($flashError === 'forbidden'): ?>
                            Keine Berechtigung diese Stelle zu ändern.
                        <?php else: ?>
                            Unbekannter Fehler.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (count($jobs) === 0): ?>
                    <p>Keine Stelle vorhanden.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Standort</th>
                                <?php if ($role === 'admin'): ?>
                                    <th>Erstellt von</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <?php
                                    $isActive = (int)$job['is_active'] === 1;
                                    $badgeClass = $isActive ? 'badge badge-success' : 'badge badge-error';
                                    $badgeText = $isActive ? 'Aktiv' : 'Inaktiv';
                                    $toggleTo = $isActive ? 0 : 1;
                                    $toggleText = $isActive ? 'Deaktivieren' : 'Veröffentlichen';
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_PATH ?>/jobs/show.php?id=<?= (int)$job['job_id'] ?>">
                                            <?= h((string)$job['title']) ?>
                                        </a>
                                    </td>
                                    <td><?= h((string)($job['location'] ?? '')) ?></td>

                                    <?php if ($role === 'admin'): ?>
                                        <td><?=h((string)($job['creator_email'] ?? ''))?></td>
                                    <?php endif; ?>

                                    <td>
                                        <span class="<?= $badgeClass ?>"><?= h($badgeText) ?></span>
                                    </td>

                                    <td>
                                        <form method="post" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="job_id" value="<?= (int)$job['job_id'] ?>">
                                            <input type="hidden" name="new_state" value="<?= (int)$toggleTo ?>">
                                            <button type="submit" class="btn btn-secondary">
                                                <?= h($toggleText) ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </body>
</html>