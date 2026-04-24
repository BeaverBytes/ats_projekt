<?php
declare(strict_types=1);

/**
 * Secure document download.
 * - RBAC: admin/recruiter only
 * - Ownership enforced via JOIN documents -> applications -> jobs
 * - No user-controlled file paths (stored_filename from DB, basename enforced)
 */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/documents.php';

startSession();
requireAnyRole(['admin', 'recruiter']);

$userId = currentUserId();
$role   = currentUserRole();

if ($userId === null || $role === null) {
    header('Location: ' . BASE_PATH . '/logout.php');
    exit;
}

$pdo = getDatabaseConnection();

// Validate document id
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documentId <= 0) {
    http_response_code(404);
    exit('Dokument nicht gefunden.');
}

// Fetch document with ownership enforcement (DB access in src/)
$doc = getDocumentByIdForDownload($pdo, $documentId, $role, $userId);

if (!$doc) {
    // Fail closed: do not reveal whether the doc exists
    http_response_code(404);
    exit('Dokument nicht gefunden.');
}

// Build safe file path (uploads/ is outside public/)
$projectRoot = realpath(__DIR__ . '/../../..'); // public/recruiter/documents -> project root
if ($projectRoot === false) {
    http_response_code(500);
    exit('Serverfehler.');
}

$uploadsDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads';
$storedName = basename((string)($doc['stored_filename'] ?? '')); // enforce basename
$filePath   = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;

// Ensure file exists and is readable
if ($storedName === '' || !is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit('Dokument nicht gefunden.');
}

// Prepare headers
$originalName = (string)($doc['original_filename'] ?? 'download.pdf');
$mimeType     = (string)($doc['mime_type'] ?? 'application/octet-stream');

// Basic hardening: if mime is empty or suspicious, fall back
if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

// Prevent caching of personal data
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $originalName) . '"');

$size = filesize($filePath);
if ($size !== false) {
    header('Content-Length: ' . (string)$size);
}

header('X-Content-Type-Options: nosniff');

// Stream file
readfile($filePath);
exit;