<?php
declare(strict_types=1);

/**
 * Admin dashboard with lightweight KPIs (counts) + navigation.
 * - Global numbers (no ownership restriction)
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/dashboard_stats.php';

startSession();
requireAuth();
requireRole('admin');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$pdo = getDatabaseConnection();

//  KPI queries 
$kpis = getAdminDashboardKpis($pdo);

$activeJobs = $kpis['activeJobs'];
$allJobs    = $kpis['allJobs'];
$allApps    = $kpis['allApps'];
$openApps   = $kpis['openApps'];
$offers     = $kpis['offers'];
$rejected   = $kpis['rejected'];
$recruiters = $kpis['recruiters'];
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Dashboard - ATS</title>
        <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/assets/style.css">
    </head>
    <body>
        <main class="container">

            <div class="form-actions">
                <a href="<?= h(BASE_PATH) ?>/" class="btn btn-secondary">← Karriere</a>
                <a href="<?= h(BASE_PATH) ?>/recruiter/dashboard.php" class="btn btn-secondary">Recruiter</a>
                <a href="<?= h(BASE_PATH) ?>/logout.php" class="btn btn-secondary">Logout</a>
            </div>

            <div class="card">
                <h1 class="page-title">Admin Dashboard</h1>

                <p class="muted">Systemweiter Überblick</p>

                <hr>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Recruiter</th>
                            <th>Stellen gesamt</th>
                            <th>Aktive Stellen</th>
                            <th>Bewerbungen gesamt</th>
                            <th>Offen</th>
                            <th>Angebote</th>
                            <th>Abgelehnt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= (int)$recruiters ?></td>
                            <td><?= (int)$allJobs ?></td>
                            <td><?= (int)$activeJobs ?></td>
                            <td><?= (int)$allApps ?></td>
                            <td><?= (int)$openApps ?></td>
                            <td><?= (int)$offers ?></td>
                            <td><?= (int)$rejected ?></td>
                        </tr>
                    </tbody>
                </table>

                <hr>

                <div class="form-actions">
                    <a href="<?= h(BASE_PATH) ?>/recruiter/jobs/index.php" class="btn btn-primary">Alle Stellen</a>
                    <a href="<?= h(BASE_PATH) ?>/recruiter/applications/index.php" class="btn btn-primary">Alle Bewerbungen</a>
                </div>
            </div>

        </main>
    </body>
</html>