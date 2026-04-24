<?php
declare(strict_types=1);

/**
 * Application detail view.
 * - Ownership enforced via JOIN jobs
 * - Status update via POST (PRG pattern)
 * - Notes: add + list (multiple notes per application)
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/applications.php';

startSession();
requireAuth();
requireAnyRole(['admin', 'recruiter']);

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$userId = currentUserId();
$role   = currentUserRole();

if ($userId === null || $role === null) {
    header('Location: ' . BASE_PATH . '/logout.php');
    exit;
}

$pdo = getDatabaseConnection();

// Validate application id
$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($applicationId <= 0) {
    http_response_code(404);
    exit('Bewerbung nicht gefunden.');
}

// Status mapping: DB value => German label
$statusLabels = [
    'submitted'  => 'Eingegangen',
    'in_review'  => 'In Prüfung',
    'interview'  => 'Interview',
    'offer'      => 'Angebot',
    'rejected'   => 'Abgelehnt',
];

$isAdmin = ($role === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    csrfVerify();

    // NOTES
    // If note_content is present, treat as "add note" action.
    if (isset($_POST['note_content'])) {
        $content = trim((string)$_POST['note_content']);

        if ($content === '') {
            header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&note_error=empty');
            exit;
        }

        if (mb_strlen($content, 'UTF-8') > 2000) {
            header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&note_error=too_long');
            exit;
        }

        // Ownership + INSERT atomar in der Service-Funktion
        $ok = addApplicationNote($pdo, $applicationId, $userId, $isAdmin, $content);

        if (!$ok) {
            header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&note_error=denied');
            exit;
        }

        header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&note_success=1');
        exit;
    }
    
    // Status update
    $newStatus = $_POST['status'] ?? '';

    updateApplicationStatus($pdo, $applicationId, (string)$newStatus, $userId, $isAdmin);

    header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&success=1');
    exit;
}

// Fetch application + documents (with ownership enforcement)
$result = getApplicationWithDocuments($pdo, $applicationId, $userId, $isAdmin);
$application = $result['application'];
$documents   = $result['documents'];

if (!$application) {
    http_response_code(404);
    exit('Bewerbung nicht gefunden oder Zugriff verweigert.');
}

// NOTES (list)
$notes = listNotesForApplication($pdo, $applicationId);

$success = isset($_GET['success']);
$noteSuccess = isset($_GET['note_success']);
$noteError = isset($_GET['note_error']) ? (string)$_GET['note_error'] : '';
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bewerbung - ATS</title>

        <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="form-actions">
                <a href="<?= h(BASE_PATH) ?>/recruiter/dashboard.php" class="btn btn-secondary">← Dashboard</a>
                <a href="<?= h(BASE_PATH) ?>/recruiter/applications/index.php" class="btn btn-secondary">Bewerbungen</a>
                <a href="<?= h(BASE_PATH) ?>/recruiter/jobs/index.php" class="btn btn-secondary">Stellen</a>
                <?php if ($role === 'admin'): ?>
                    <a href="<?= h(BASE_PATH) ?>/admin/dashboard.php" class="btn btn-secondary">Admin</a>
                <?php endif; ?>
                <a href="<?= h(BASE_PATH) ?>/logout.php" class="btn btn-secondary">Logout</a>
            </div>

            <div class="card">
                <h1 class="page-title">Bewerbung #<?= (int)$applicationId ?></h1>

                <?php if ($success): ?>
                    <div class="alert alert-success">Status wurde aktualisiert.</div>
                <?php endif; ?>

                <?php if ($noteSuccess): ?>
                    <div class="alert alert-success">Notiz wurde gespeichert.</div>
                <?php endif; ?>

                <?php if ($noteError === 'empty'): ?>
                    <div class="alert alert-danger">Notiz darf nicht leer sein.</div>
                <?php elseif ($noteError === 'too_long'): ?>
                    <div class="alert alert-danger">Notiz ist zu lang (max. 2000 Zeichen).</div>
                <?php elseif ($noteError === 'denied'): ?>
                    <div class="alert alert-danger">Keine Berechtigung für diese Bewerbung.</div>
                <?php endif; ?>

                <div class="grid">
                    <div>
                        <h2>Stelle</h2>
                        <p>
                            <strong><?= h((string)($application['job_title'] ?? '')) ?></strong>
                            <?php if (!empty($application['job_location'])): ?>
                                <div class="muted"><?= h((string)$application['job_location']) ?></div>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div>
                        <h2>Bewerber:in</h2>

                        <div><strong><?= h(trim((string)($application['applicant_first_name'] ?? '') . ' ' . (string)($application['applicant_last_name'] ?? ''))) ?></strong></div>

                        <div class="muted">E-Mail:</div>
                        <div><?= h((string)($application['applicant_email'] ?? '')) ?></div>

                        <?php if (!empty($application['applicant_phone'])): ?>
                            <div class="muted" style="margin-top:6px;">Telefon:</div>
                            <div><?= h((string)$application['applicant_phone']) ?></div>
                        <?php endif; ?>

                        <span class="field-label">Motivation</span>
                        <div class="box">
                            <?php if (!empty($application['motivation'])): ?>
                                <?= nl2br(h((string)$application['motivation'])) ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h2>Status</h2>
                <form method="post">
                    <?= csrfField(); ?>
                    <div class="form-actions">
                        <select name="status">
                            <?php $currentStatus = (string)($application['status'] ?? 'submitted'); ?>
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <option value="<?= h($value) ?>" <?= $currentStatus === $value ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Status speichern</button>
                    </div>
                </form>

                <h2>Dokumente</h2>
                <?php if (empty($documents)): ?>
                    <p>Keine Dokumente vorhanden.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($documents as $d): ?>
                            <li>
                                <a href="<?= h(BASE_PATH) ?>/recruiter/documents/download.php?id=<?= (int)($d['document_id'] ?? 0) ?>">
                                    <?= h((string)($d['original_filename'] ?? '')) ?>
                                </a>
                                <span class="muted">
                                    (<?= h((string)($d['uploaded_at'] ?? '')) ?>)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post">
                    <?= csrfField(); ?>
                    <div class="form-group">
                        <textarea name="note_content" rows="4" maxlength="2000" placeholder="Notiz hinzufügen..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-secondary">Notiz speichern</button>
                </form>

                <?php if (empty($notes)): ?>
                    <p class="muted">Noch keine Notizen vorhanden.</p>
                <?php else: ?>
                    <div class="notes">
                        <?php foreach ($notes as $n): ?>
                            <div class="note">
                                <div class="muted">
                                    <?= h((string)($n['author_email'] ?? '')) ?> · <?= h((string)($n['created_at'] ?? '')) ?>
                                </div>
                                <div><?= nl2br(h((string)($n['content'] ?? ''))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </body>
</html>