<?php
declare(strict_types=1);

/**
 * Document-related DB helpers.
 *
 * Provides secure access to uploaded application documents,
 * with RBAC and ownership enforced at the SQL level.
 */

/**
 * Fetch a document row for download.
 *
 * - Admin: any document.
 * - Recruiter: only documents attached to applications for jobs they own.
 *
 * Returns null if the document does not exist or access is denied.
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