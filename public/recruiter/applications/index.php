<?php
declare(strict_types=1);

/**
 * Application list for recruiters/admins.
 * - Admin sees all applications
 * - Recruiter sees only applications for jobs they own (jobs.created_by_user_id = current user)
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/users.php';
require_once __DIR__ . '/../../../src/applications.php';
require_once __DIR__ . '/../../../src/view_helpers.php';

startSession();
requireAnyRole(['admin', 'recruiter']);

// Status mapping: DB value => German label
$statusLabels = [
    'submitted'  => 'Eingegangen',
    'in_review'  => 'In Prüfung',
    'interview'  => 'Interview',
    'offer'      => 'Angebot',
    'rejected'   => 'Abgelehnt',
];

$userId = currentUserId();
$role = currentUserRole();

// Defensive: defaults to recruiter scope if role is missing or unexpected
if ($userId === null || $role === null) {
    header('Location: ' . BASE_PATH . '/logout.php');
    exit;
}

$pdo = getDatabaseConnection();

//  Admin recruiter filter 
$selectedRecruiterId = 0;
$recruiters = [];

if ($role === 'admin') {
    $selectedRecruiterId = isset($_GET['recruiter_id']) ? (int)$_GET['recruiter_id'] : 0;
    $recruiters = listRecruiters($pdo);
}

$isAdmin = ($role === 'admin');
$applications = listApplications($pdo, $userId, $isAdmin, $selectedRecruiterId);

// Optional success feedback via PRG pattern (?success=1)
$success = isset($_GET['success']) ? (string)$_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bewerbungen - ATS</title>

        <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="form-actions">
                <a href="<?= h(BASE_PATH) ?>/recruiter/dashboard.php" class="btn btn-secondary">← Dashboard</a>
                <a href="<?= h(BASE_PATH) ?>/recruiter/jobs/index.php" class="btn btn-secondary">Stellen</a>
                <?php if ($role === 'admin'): ?>
                    <a href="<?= h(BASE_PATH) ?>/admin/dashboard.php" class="btn btn-secondary">Admin</a>
                <?php endif; ?>
                <a href="<?= h(BASE_PATH) ?>/logout.php" class="btn btn-secondary">Logout</a>
            </div>

            <div class="card">
                <h1 class="page-title">Bewerbungen</h1>

                <?php if ($success): ?>
                    <div class="alert alert-success">Aktion erfolgreich ausgeführt.</div>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <form method="get">
                        <div class="form-actions">
                            <label for="recruiter_id"><strong>Recruiter:</strong></label>

                            <select name="recruiter_id" id="recruiter_id">
                                <option value="0" <?= $selectedRecruiterId === 0 ? 'selected' : '' ?>>Alle</option>
                                <?php foreach ($recruiters as $r): ?>
                                    <option value="<?= (int)$r['user_id'] ?>" <?= ((int)$r['user_id'] === $selectedRecruiterId) ? 'selected' : '' ?>>
                                        <?= h((string)$r['email']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn btn-secondary">Filtern</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (empty($applications)): ?>
                    <p>Keine Bewerbungen vorhanden.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bewerber:in</th>
                                <th>E-Mail</th>
                                <th>Stelle</th>
                                <th>Status</th>
                                <th>Eingang</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $a): ?>
                                <tr>
                                    <td><?= (int)$a['application_id'] ?></td>
                                    <td><?= h(trim((string)($a['applicant_first_name'] ?? '') . ' ' . (string)($a['applicant_last_name'] ?? ''))) ?></td>
                                    <td><?= h((string)($a['applicant_email'] ?? '')) ?></td>
                                    <td>
                                        <?= h((string)($a['job_title'] ?? '')) ?>
                                        <?php if (!empty($a['job_location'])): ?>
                                            <div class="muted"><?= h((string)$a['job_location']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $statusValue = (string)($a['status'] ?? 'submitted'); ?>
                                        <span class="badge badge-<?= h($statusValue) ?>">
                                            <?= h($statusLabels[$statusValue] ?? $statusValue) ?>
                                        </span>
                                    </td>
                                    <td><?= h((string)($a['created_at'] ?? '')) ?></td>
                                    <td>
                                        <a class="btn btn-primary"
                                           href="<?= h(BASE_PATH) ?>/recruiter/applications/show.php?id=<?= (int)$a['application_id'] ?>">
                                            Details
                                        </a>
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