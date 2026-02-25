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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
     csrfVerify();

    // NOTES (minimal add)
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

        // Insert note (ownership is implicitly enforced by this page access)
        $stmtNote = $pdo->prepare("
            INSERT INTO notes (application_id, user_id, content, created_at)
            VALUES (:appId, :userId, :content, CURRENT_TIMESTAMP)
        ");
        $stmtNote->execute([
            ':appId'   => $applicationId,
            ':userId'  => $userId,
            ':content' => $content,
        ]);

        header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&note_success=1');
        exit;
    }
    
    // Status update
    $newStatus = $_POST['status'] ?? '';

    // Ownership-safe update using JOIN
    if ($role === 'admin') {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = :status
            WHERE application_id = :id
        ");
        $stmt->execute([
            ':status' => $newStatus,
            ':id'     => $applicationId,
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = :status
            WHERE application_id = :id
              AND job_id IN (
                    SELECT job_id
                    FROM jobs
                    WHERE created_by_user_id = :uid
              )
        ");
        $stmt->execute([
            ':status' => $newStatus,
            ':id'     => $applicationId,
            ':uid'    => $userId,
        ]);
    }

    header('Location: ' . BASE_PATH . '/recruiter/applications/show.php?id=' . $applicationId . '&success=1');
    exit;
}

// Fetch application (with ownership enforcement)
$sql = "
    SELECT
        a.*,
        j.title AS job_title,
        j.location AS job_location
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.application_id = :id
";

$params = [':id' => $applicationId];

if ($role === 'recruiter') {
    $sql .= " AND j.created_by_user_id = :uid ";
    $params[':uid'] = $userId;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    http_response_code(404);
    exit('Bewerbung nicht gefunden oder Zugriff verweigert.');
}

// Fetch documents for this application
$stmtDocs = $pdo->prepare("
    SELECT document_id, original_filename, uploaded_at
    FROM documents
    WHERE application_id = :id
    ORDER BY uploaded_at ASC
");
$stmtDocs->execute([':id' => $applicationId]);
$documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

// NOTES (list)
$stmtNotes = $pdo->prepare("
    SELECT
        n.note_id,
        n.content,
        n.created_at,
        u.email AS author_email
    FROM notes n
    JOIN users u ON n.user_id = u.user_id
    WHERE n.application_id = :id
    ORDER BY n.created_at DESC
");
$stmtNotes->execute([':id' => $applicationId]);
$notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

$success = isset($_GET['success']);
$noteSuccess = isset($_GET['note_success']);
$noteError = isset($_GET['note_error']) ? (string)$_GET['note_error'] : ''
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bewerbung #<?= (int)$application['application_id'] ?> - ATS</title>

        <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/assets/style.css">
    </head>
    <body>
        <main class="container">
            <div class="form-actions">
                <a href="<?= h(BASE_PATH) ?>/recruiter/applications/index.php" class="btn btn-secondary">← Zur Übersicht</a>
                <a href="<?= h(BASE_PATH) ?>/logout.php" class="btn btn-secondary">Logout</a>
            </div>

            <div class="card">
                <h1 class="page-title">Bewerbung #<?= (int)$application['application_id'] ?></h1>

                <?php if ($success): ?>
                    <div class="alert alert-success">Status erfolgreich aktualisiert.</div>
                <?php endif; ?>

                <?php if ($noteSuccess): ?>
                    <div class="alert alert-success">Notiz gespeichert.</div>
                <?php endif; ?>

                <?php if ($noteError === 'empty'): ?>
                    <div class="alert alert-error">Bitte eine Notiz eingeben.</div>
                <?php elseif ($noteError === 'too_long'): ?>
                    <div class="alert alert-error">Notiz ist zu lang (max. 2000 Zeichen).</div>
                <?php endif; ?>

                <p class="muted">
                    Eingang: <?= h((string)$application['created_at']) ?>
                </p>

                <hr>

                <h2>Stelle</h2>
                <p><strong><?= h((string)$application['job_title']) ?></strong></p>
                <?php if (!empty($application['job_location'])): ?>
                    <p><strong>Ort:</strong> <?= h((string)$application['job_location']) ?></p>
                <?php endif; ?>

                <hr>

                <h2>Bewerber:in</h2>
                <p>
                    <?= h(trim((string)$application['applicant_first_name'] . ' ' . (string)$application['applicant_last_name'])) ?>
                </p>
                <p><strong>E-Mail:</strong> <?= h((string)$application['applicant_email']) ?></p>
                <?php if (!empty($application['applicant_phone'])): ?>
                    <p><strong>Telefon:</strong> <?= h((string)$application['applicant_phone']) ?></p>
                <?php endif; ?>

                <hr>

                <h2>Motivation</h2>
                <p class="job-description"><?= nl2br(h((string)($application['motivation'] ?? ''))) ?></p>

                <hr>

                <h2>Status</h2>
                <?php $statusValue = (string)$application['status']; ?>
                <p>
                    Aktueller Status:
                    <span class="badge badge-<?= h($statusValue) ?>">
                        <?= h($statusLabels[$statusValue] ?? $statusValue) ?>
                    </span>
                </p>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="form-actions">
                        <select name="status" required>
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <option value="<?= h($value) ?>" <?= ((string)$application['status'] === $value) ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>

                <hr>

                <h2>Dokumente</h2>
                <?php if (empty($documents)): ?>
                    <p>Keine Dokumente vorhanden.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($documents as $doc): ?>
                            <li>
                                <?= h((string)$doc['original_filename']) ?>
                                <?php if (!empty($doc['uploaded_at'])): ?>
                                    <span class="muted"> (<?= h((string)$doc['uploaded_at']) ?>)</span>
                                <?php endif; ?>
                                – <a href="<?= h(BASE_PATH) ?>/recruiter/documents/download.php?id=<?= (int)$doc['document_id'] ?>">Download</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <hr>

                <h2>Notizen</h2>

                <form method="post">
                    <?= csrfField() ?>
                    <div class="form-actions" style="flex-direction: column; align-items: stretch;">
                        <textarea name="note_content" rows="4" placeholder="Neue Notiz…" required></textarea>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Notiz hinzufügen</button>
                        </div>
                    </div>
                </form>

                <?php if (empty($notes)): ?>
                    <p class="muted">Noch keine Notizen.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($notes as $n): ?>
                            <li>
                                <strong><?= h((string)$n['author_email']) ?></strong>
                                <span class="muted"> (<?= h((string)$n['created_at']) ?>)</span>
                                <div><?= nl2br(h((string)$n['content'])) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>
        </main>
    </body>
</html>