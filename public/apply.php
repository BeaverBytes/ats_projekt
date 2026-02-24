<?php
declare(strict_types=1);

/**
 * Public application form and
 * secure multi-file upload
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/jobs.php';
require_once __DIR__ . '/../src/applications.php';

$pdo = getDatabaseConnection();

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Normalize $_FILES for multiple uploads to a flat list.
function normalizeFilesArray(array $files): array {
    $normalized = [];

    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $normalized[] = [
            'name' => (string)($files['name'][$i] ?? ''),
            'type' => (string)($files['type'][$i] ?? ''),
            'tmp_name' => (string)($files['tmp_name'][$i] ?? ''),
            'error' => (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($files['size'][$i] ?? 0),
        ];
    }

    return $normalized;
}

// Validate uploaded PDFs (count, size, MIME).
function validatePdfUploads(array $files, int $maxFiles, int $maxBytesPerFile): array {
    $errors = [];
    $valid = [];

    // Filter out empty slots (no file chosen)
    $nonEmpty = [];
    foreach ($files as $f) {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $nonEmpty[] = $f;
        }
    }

    if (count($nonEmpty) === 0) {
        return ['ok' => true, 'errors' => [], 'validFiles' => []];
    }

    if (count($nonEmpty) > $maxFiles) {
        $errors[] = 'Bitte maximal ' . $maxFiles . ' PDF-Dateien hochladen.';
        return ['ok' => false, 'errors' => $errors, 'validFiles' => []];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($nonEmpty as $idx => $file) {
        $err = (int)$file['error'];

        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload-Fehler bei Datei #' . ($idx + 1) . '.';
            continue;
        }

        $size = (int)$file['size'];
        if ($size <= 0 || $size > $maxBytesPerFile) {
            $errors[] = 'Datei #' . ($idx + 1) . ' ist zu groß (max. ' . (int)($maxBytesPerFile / 1024 / 1024) . ' MB).';
            continue;
        }

        $tmp = (string)$file['tmp_name'];
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $errors[] = 'Ungültiger Upload bei Datei #' . ($idx + 1) . '.';
            continue;
        }

        $mime = $finfo->file($tmp);
        if ($mime !== 'application/pdf') {
            $errors[] = 'Datei #' . ($idx + 1) . ' ist kein gültiges PDF.';
            continue;
        }

        $valid[] = $file;
    }

    return [
        'ok' => count($errors) === 0,
        'errors' => $errors,
        'validFiles' => $valid,
    ];
}

// Generate a safe stored filename.
function generateStoredFilename(string $originalName): string {
    // Force .pdf extension.
    $random = bin2hex(random_bytes(16));
    return $random . '.pdf';
}

$MAX_FILES = 3;
$MAX_MB_PER_FILE = 2;
$MAX_BYTES_PER_FILE = $MAX_MB_PER_FILE * 1024 * 1024;

// uploads directory (outside public)
$uploadDir = realpath(__DIR__ . '/../uploads');
if ($uploadDir === false) {
    http_response_code(500);
    echo 'Serverfehler: Upload-Verzeichnis nicht gefunden.';
    exit;
}

// GET job_id
$jobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($jobId === false || $jobId === null) {
    http_response_code(400);
    echo 'Ungültige Stelle.';
    exit;
}

// Load job (public can only apply to active jobs)
$job = findActiveJobById($pdo, (int)$jobId);
if ($job === null) {
    http_response_code(404);
    echo 'Stelle nicht gefunden oder nicht aktiv.';
    exit;
}

// State for re-rendering form after validation errors
$errors = [];
$values = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'motivation' => '',
    'consent' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Merge job_id into POST for validation helper
    $post = $_POST;
    $post['job_id'] = (string)$jobId;

    $res = validateApplicationInput($post);
    $values = [
        'first_name' => (string)($res['data']['first_name'] ?? ''),
        'last_name' => (string)($res['data']['last_name'] ?? ''),
        'email' => (string)($res['data']['email'] ?? ''),
        'phone' => (string)($res['data']['phone'] ?? ''),
        'motivation' => (string)($res['data']['motivation'] ?? ''),
        'consent' => (int)($res['data']['consent'] ?? 0),
    ];

    if (!$res['ok']) {
        $errors = array_values($res['errors']);
    }

    $files = normalizeFilesArray($_FILES['documents'] ?? []);
    $uploadCheck = validatePdfUploads($files, $MAX_FILES, $MAX_BYTES_PER_FILE);
    if (!$uploadCheck['ok']) {
        $errors = array_merge($errors, $uploadCheck['errors']);
    }

    if (count($errors) === 0) {
        $storedFiles = []; // Track stored files for rollback cleanup if needed

        try {
            $pdo->beginTransaction();

            $applicationId = createApplication($pdo, $res['data']);

            // Store each file + metadata
            foreach ($uploadCheck['validFiles'] as $file) {
                $original = (string)$file['name'];
                $stored = generateStoredFilename($original);
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $stored;

                // move first, then write metadata (still inside transaction)
                if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
                    throw new RuntimeException('Upload konnte nicht gespeichert werden.');
                }

                $storedFiles[] = $targetPath;

                // Metadata insert
                addDocument($pdo, $applicationId, [
                    'original_filename' => $original,
                    'stored_filename' => $stored,
                    'mime_type' => 'application/pdf',
                    'size_bytes' => (int)$file['size'],
                ]);
            }

            $pdo->commit();

            header('Location: ' . BASE_PATH . '/apply.php?job_id=' . (int)$jobId . '&success=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Cleanup stored files if any were written
            foreach ($storedFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $errors[] = 'Serverfehler: Bewerbung konnte nicht gespeichert werden. Bitte erneut versuchen.';
        }
    }
}

$success = filter_input(INPUT_GET, 'success', FILTER_VALIDATE_INT);
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bewerben – <?= h((string)$job['title']) ?></title>
        <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/assets/style.css">
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>Bewerben</h1>
                <p><strong>Stelle:</strong> <?= h((string)$job['title']) ?></p>

                <?php if ($success === 1): ?>
                    <div class="alert alert-success">
                        Bewerbung erfolgreich gesendet. Vielen Dank!
                    </div>
                <?php endif; ?>

                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-error">
                        <strong>Bitte prüfen:</strong>
                        <ul>
                            <?php foreach ($errors as $msg): ?>
                                <li><?= h((string)$msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= h(BASE_PATH) ?>/apply.php?job_id=<?= (int)$jobId ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="first_name">Vorname *</label>
                        <input id="first_name" name="first_name" type="text" required maxlength="100"
                            value="<?= h((string)$values['first_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Nachname *</label>
                        <input id="last_name" name="last_name" type="text" required maxlength="100"
                            value="<?= h((string)$values['last_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">E-Mail *</label>
                        <input id="email" name="email" type="email" required maxlength="255"
                            value="<?= h((string)$values['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon (optional)</label>
                        <input id="phone" name="phone" type="text" maxlength="30"
                            value="<?= h((string)$values['phone']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="motivation">Motivation (optional)</label>
                        <textarea id="motivation" name="motivation" rows="6" maxlength="4000"><?= h((string)$values['motivation']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="documents">Dokumente (optional - nur PDF max. <?= (int)$MAX_FILES ?> Dateien auf einmal wählen, je max. <?= (int)$MAX_MB_PER_FILE ?> MB)</label>
                        <input id="documents" name="documents[]" type="file" accept="application/pdf" multiple>
                    </div>

                    <div class="form-group checkbox-row">
                        <label>
                            <input type="checkbox" name="consent" value="1" <?= ((int)$values['consent'] === 1) ? 'checked' : '' ?>>
                            Ich stimme der Verarbeitung meiner Daten zum Zweck der Bewerbung zu. *
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Bewerbung absenden</button>
                        <a class="btn btn-secondary" href="<?= h(BASE_PATH) ?>/jobs/show.php?id=<?= (int)$jobId ?>">Zurück zur Stelle</a>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>