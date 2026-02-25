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

startSession();
requireAuth();
requireRole('admin');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$pdo = getDatabaseConnection();

//  KPI queries 
$activeJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE is_active = 1")->fetchColumn();
$allJobs    = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

$allApps    = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

$openAppsStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications
    WHERE status IN ('submitted', 'in_review', 'interview')
");
$openAppsStmt->execute();
$openApps = (int)$openAppsStmt->fetchColumn();

$offers   = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'offer'")->fetchColumn();
$rejected = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();

$recruiters = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'")->fetchColumn();
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

                <p class="muted">Navigation</p>

                <div class="form-actions">
                    <a href="<?= h(BASE_PATH) ?>/recruiter/applications/index.php" class="btn btn-primary">Alle Bewerbungen</a>
                    <a href="<?= h(BASE_PATH) ?>/recruiter/jobs/index.php" class="btn btn-primary">Alle Stellen</a>
                </div>

                <hr>

                <div class="form-actions">
                    <a href="<?= h(BASE_PATH) ?>/recruiter/jobs/new.php" class="btn btn-secondary">Neue Stelle anlegen</a>
                    <a href="<?= h(BASE_PATH) ?>/jobs/index.php" class="btn btn-secondary">Stellenliste (öffentlich)</a>
                </div>
            </div>

        </main>
    </body>
</html>