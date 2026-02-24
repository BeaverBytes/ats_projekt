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

startSession();
requireAuth();
requireAnyRole(['admin', 'recruiter']);

//Escape HTML output (XSS mitigation for rendered content).
 function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$userId = currentUserId();
$role = currentUserRole();

// Optional "belt & suspenders" – keep it fail-closed
if ($userId === null || $role === null) {
    header('Location: ' . BASE_PATH . '/logout.php');
    exit;
}

$pdo = getDatabaseConnection();

/**
 * Query applications with ownership check via JOIN jobs.
 * NOTE: Ownership is enforced through jobs.created_by_user_id, not only by application_id.
 */
$sql = "
    SELECT
        a.application_id,
        a.applicant_first_name,
        a.applicant_last_name,
        a.applicant_email,
        a.status,
        a.created_at,
        j.title AS job_title,
        j.location AS job_location
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
";

$params = [];
if ($role === 'recruiter') {
    $sql .= " WHERE j.created_by_user_id = :uid ";
    $params[':uid'] = $userId;
}

$sql .= " ORDER BY a.created_at DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                    <td><span class="badge"><?= h((string)($a['status'] ?? 'submitted')) ?></span></td>
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