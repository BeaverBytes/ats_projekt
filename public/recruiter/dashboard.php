<?php
declare(strict_types=1);

/**
 * public/recruiter/dashboard.php
 *
 * Dashboard with lightweight KPIs (counts).
 * - Admin: global counts
 * - Recruiter: own counts via ownership (JOIN jobs.created_by_user_id)
 *
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/dashboard_stats.php';
require_once __DIR__ . '/../../src/view_helpers.php';

startSession();
requireAnyRole(['admin', 'recruiter']);

$userId = currentUserId();
$role   = currentUserRole();

if ($userId === null || $role === null) {
    header('Location: ' . BASE_PATH . '/logout.php');
    exit;
}

$pdo = getDatabaseConnection();

$kpis = getRecruiterDashboardKpis($pdo, $role, $userId);

$activeJobs = $kpis['activeJobs'];
$allApps    = $kpis['allApps'];
$openApps   = $kpis['openApps'];
$offers     = $kpis['offers'];
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