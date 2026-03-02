<?php
declare(strict_types=1);

/**
 * src/documents.php
 *
 * Document-related DB helpers.
 * Used for secure downloads with ownership checks.
 * Fetch a document row for download with RBAC/ownership enforcement.
 */
function getDocumentByIdForDownload(PDO $pdo, int $documentId, string $role, int $userId): ?array
{
    $sql = "
        SELECT
            d.document_id,
            d.original_filename,
            d.stored_filename,
            d.mime_type,
            d.size_bytes
        FROM documents d
        JOIN applications a ON d.application_id = a.application_id
        JOIN jobs j ON a.job_id = j.job_id
        WHERE d.document_id = :docId
    ";
    $params = [':docId' => $documentId];

    if ($role === 'recruiter') {
        $sql .= " AND j.created_by_user_id = :uid ";
        $params[':uid'] = $userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    return [
        'document_id'        => (int)$row['document_id'],
        'original_filename'  => (string)($row['original_filename'] ?? ''),
        'stored_filename'    => (string)($row['stored_filename'] ?? ''),
        'mime_type'          => (string)($row['mime_type'] ?? ''),
        'size_bytes'         => (int)($row['size_bytes'] ?? 0),
    ];
}