<?php
declare(strict_types=1);

/**
 * public/recruiter/dashboard.php
 *
 * Dashboard with lightweight KPIs (counts).
 * - Admin: global counts
 * - Recruiter: own counts via ownership (JOIN jobs.created_by_user_id)
 *
 * UI: German | Comments: English
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';

startSession();
requireAuth();
requireAnyRole(['admin', 'recruiter']);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$userId = currentUserId();
$role   = currentUserRole();

if ($userId === null || $role === null) {
    header('Location: ' . BASE_PATH . '/logout.php');
    exit;
}

$pdo = getDatabaseConnection();

// KPI queries (minimal)
if ($role === 'admin') {

    $activeJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE is_active = 1")->fetchColumn();
    $allApps    = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

    $openAppsStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM applications
        WHERE status IN ('submitted', 'in_review', 'interview')
    ");
    $openAppsStmt->execute();
    $openApps = (int)$openAppsStmt->fetchColumn();

    $offers = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'offer'")->fetchColumn();

} else {

    $activeJobsStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM jobs
        WHERE is_active = 1
          AND created_by_user_id = :uid
    ");
    $activeJobsStmt->execute([':uid' => $userId]);
    $activeJobs = (int)$activeJobsStmt->fetchColumn();

    $allAppsStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        WHERE j.created_by_user_id = :uid
    ");
    $allAppsStmt->execute([':uid' => $userId]);
    $allApps = (int)$allAppsStmt->fetchColumn();

    $openAppsStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        WHERE j.created_by_user_id = :uid
          AND a.status IN ('submitted', 'in_review', 'interview')
    ");
    $openAppsStmt->execute([':uid' => $userId]);
    $openApps = (int)$openAppsStmt->fetchColumn();

    $offersStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        WHERE j.created_by_user_id = :uid
          AND a.status = 'offer'
    ");
    $offersStmt->execute([':uid' => $userId]);
    $offers = (int)$offersStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Recruiter Dashboard - ATS</title>
        <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/assets/style.css">
    </head>
    <body>
        <main class="container">

            <div class="form-actions">
                <a href="<?= h(BASE_PATH) ?>/" class="btn btn-secondary">← Karriere</a>
                <?php if ($role === 'admin'): ?>
                        <a href="<?= h(BASE_PATH) ?>/admin/dashboard.php" class="btn btn-secondary">Admin Dashboard</a>
                <?php endif; ?>
                <a href="<?= h(BASE_PATH) ?>/logout.php" class="btn btn-secondary">Logout</a>
            </div>

            <div class="card">
                <h1 class="page-title">Recruiter Dashboard</h1>

                <p class="muted">
                    Überblick <?= $role === 'admin' ? '(Admin – global)' : '(Recruiter – eigene Stellen)' ?>
                </p>

                <hr>

                <!-- Minimal KPI row -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Aktive Stellen</th>
                            <th>Bewerbungen gesamt</th>
                            <th>Offen</th>
                            <th>Angebote</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= (int)$activeJobs ?></td>
                            <td><?= (int)$allApps ?></td>
                            <td><?= (int)$openApps ?></td>
                            <td><?= (int)$offers ?></td>
                        </tr>
                    </tbody>
                </table>

                <hr>

                <div class="form-actions">
                    <a href="<?= h(BASE_PATH) ?>/recruiter/jobs/index.php" class="btn btn-primary">Stellen verwalten</a>
                    <a href="<?= h(BASE_PATH) ?>/recruiter/applications/index.php" class="btn btn-primary">Bewerbungen ansehen</a>
                </div>
            </div>
        </main>
    </body>
</html>