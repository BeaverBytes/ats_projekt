<?php
declare(strict_types=1);

/**
 * Core application service for the ATS.
 * Provides validation, persistence,
 * authorization-aware queries and status management
 */

const APPLICATION_STATUS = ['submitted', 'in_review', 'interview', 'offer', 'rejected',];

/**
 * Trim a string and convert null to empty string.
 *
 * Used to clean user input before validation and storage.
 */
function normalize_string(?string $value): string {
    return trim((string)$value);
}


/**
 * Validate applicant form data.
 *
 * Checks job_id, name, email, phone (optional), motivation (optional)
 * and the GDPR consent flag. Does NOT validate the uploaded files —
 * that is handled separately in apply.php.
 *
 * Returns an array of error messages (empty if valid).
 */
function validateApplicationInput(array $post): array {
    $errors = [];

    $firstName = normalize_string($post['first_name'] ?? '');
    $lastName   = normalize_string($post['last_name'] ?? '');
    $email      = normalize_string($post['email'] ?? '');
    $phone      = normalize_string($post['phone'] ?? '');
    $motivation = normalize_string($post['motivation'] ?? '');
    $consent    = isset($post['consent']) && (string)$post['consent'] === '1';

    // job_id must be an int > 0
    $jobIdRaw = $post['job_id'] ?? '';
    $jobId = filter_var($jobIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($jobId === false) {
        $errors['job_id'] = 'Ungültige Stelle.';
        $jobId = 0;
    }

    // Basic validations
    if ($firstName === '' || mb_strlen($firstName) > 100) {
        $errors['first_name'] = 'Bitte Vornamen angeben (max. 100 Zeichen).';
    }
    if ($lastName === '' || mb_strlen($lastName) > 100) {
        $errors['last_name'] = 'Bitte Nachnamen angeben (max. 100 Zeichen).';
    }
    if ($email === '' || mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Bitte gültige E-Mail angeben.';
    }
    if ($phone !== '' && mb_strlen($phone) > 30) {
        $errors['phone'] = 'Telefonnummer ist zu lang (max. 30 Zeichen).';
    }
    if ($motivation !== '' && mb_strlen($motivation) > 4000) {
        $errors['motivation'] = 'Motivation ist zu lang (max. 4000 Zeichen).';
    }
    if (!$consent) {
        $errors['consent'] = 'Bitte stimmen sie der Verarbeitung Ihrer Daten zu.';
    }

    return [
        'ok' => count($errors) === 0,
        'errors' => $errors,
        'data' => [
            'job_id' => (int)$jobId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'motivation' => $motivation,
            'consent' => $consent ? 1 : 0,
        ],
    ];
}

/**
 * Insert a new application row.
 *
 * Caller is responsible for input validation; this function trusts $data
 * and returns the new application_id.
 */
function createApplication(PDO $pdo, array $data): int {
    $sql = "INSERT INTO applications (
            job_id,
            applicant_first_name,
            applicant_last_name,
            applicant_email,
            applicant_phone,
            motivation,
            status,
            consent,
            created_at
        )
        VALUES (
            :job_id,
            :first_name,
            :last_name,
            :email,
            :phone,
            :motivation,
            :status,
            :consent,
            CURRENT_TIMESTAMP
        )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':job_id' => (int)$data['job_id'],
        ':first_name' => (string)$data['first_name'],
        ':last_name' => (string)$data['last_name'],
        ':email' => (string)$data['email'],
        ':phone' => (string)$data['phone'],
        ':motivation' => (string)$data['motivation'],
        ':status' => 'submitted',
        ':consent' => (int)$data['consent'],
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Persist document metadata for an already stored file.
 * (File storage itself is handled by apply.php)
 */
function addDocument(PDO $pdo, int $applicationId, array $doc): void {
    $sql = "
        INSERT INTO documents (
            application_id,
            original_filename,
            stored_filename,
            mime_type,
            size_bytes,
            uploaded_at
        )
        VALUES (
            :application_id,
            :original_filename,
            :stored_filename,
            :mime_type,
            :size_bytes,
            CURRENT_TIMESTAMP
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':application_id' => $applicationId,
        ':original_filename' => (string)$doc['original_filename'],
        ':stored_filename' => (string)$doc['stored_filename'],
        ':mime_type' => (string)$doc['mime_type'],
        ':size_bytes' => (int)$doc['size_bytes'],
    ]);
}

/**
 * List applications for recruiter/admin views.
 *
 * - Admin: global list, optionally filtered by recruiter via $selectedRecruiterId.
 * - Recruiter: only applications for jobs they own (jobs.created_by_user_id).
 *
 * Ownership is enforced in SQL, not in PHP — recruiters cannot see
 * applications outside their scope regardless of the URL they request.
 */
function listApplications(PDO $pdo, int $currentUserId, bool $isAdmin, int $selectedRecruiterId = 0): array {
    // Admin: global list, optional recruiter filter
    if ($isAdmin) {

        // Optional admin filter: only applications for jobs created by a specific recruiter
        if ($selectedRecruiterId > 0) {
            $sql = "
                SELECT
                    a.application_id,
                    a.job_id,
                    a.applicant_first_name,
                    a.applicant_last_name,
                    a.applicant_email,
                    a.status,
                    a.created_at,
                    j.title AS job_title,
                    j.created_by_user_id
                FROM applications a
                JOIN jobs j ON j.job_id = a.job_id
                WHERE j.created_by_user_id = :rid
                ORDER BY a.created_at DESC
                LIMIT 200
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['rid' => $selectedRecruiterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Admin without filter: static query is fine
        $sql = "
            SELECT
                a.application_id,
                a.job_id,
                a.applicant_first_name,
                a.applicant_last_name,
                a.applicant_email,
                a.status,
                a.created_at,
                j.title AS job_title,
                j.created_by_user_id
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            ORDER BY a.created_at DESC
            LIMIT 200
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Recruiter: scoped by ownership
    $sql = "
        SELECT
            a.application_id,
            a.job_id,
            a.applicant_first_name,
            a.applicant_last_name,
            a.applicant_email,
            a.status,
            a.created_at,
            j.title AS job_title,
            j.created_by_user_id
        FROM applications a
        JOIN jobs j ON j.job_id = a.job_id
        WHERE j.created_by_user_id = :uid
        ORDER BY a.created_at DESC
        LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $currentUserId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Load a single application with its uploaded documents.
 *
 * Ownership: recruiters can only access applications for jobs they own;
 * admins can access any application. Returns ['application' => null, 'documents' => []]
 * when access is denied or the application does not exist.
 */
function getApplicationWithDocuments(PDO $pdo, int $applicationId, int $currentUserId, bool $isAdmin): array {
    // Authorization: recruiter can only access applications for their own jobs.
    if ($isAdmin) {
        $sqlApp = "
            SELECT
                a.*,
                j.title AS job_title,
                j.location AS job_location,
                j.created_by_user_id
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            WHERE a.application_id = :aid
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sqlApp);
        $stmt->execute([':aid' => $applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        $sqlApp = "
            SELECT
                a.*,
                j.title AS job_title,
                j.location AS job_location,
                j.created_by_user_id
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            WHERE a.application_id = :aid
              AND j.created_by_user_id = :uid
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sqlApp);
        $stmt->execute([':aid' => $applicationId, ':uid' => $currentUserId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if ($app === null) {
        return ['application' => null, 'documents' => []];
    }

    $sqlDocs = "
        SELECT
            document_id,
            application_id,
            original_filename,
            stored_filename,
            mime_type,
            size_bytes,
            uploaded_at
        FROM documents
        WHERE application_id = :aid
        ORDER BY uploaded_at ASC
    ";
    $stmt = $pdo->prepare($sqlDocs);
    $stmt->execute([':aid' => $applicationId]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return ['application' => $app, 'documents' => $docs];
}

/**
 * Update the status of an application.
 *
 * Allowed status values are defined in APPLICATION_STATUS. Recruiters can
 * only update applications for jobs they own; admins can update any.
 *
 * Returns true if the row was updated, false if the status is invalid
 * or the user lacks permission.
 */
function updateApplicationStatus(PDO $pdo, int $applicationId, string $newStatus, int $currentUserId, bool $isAdmin): bool {
    if (!in_array($newStatus, APPLICATION_STATUS, true)) {
        return false;
    }

    if ($isAdmin) {
        $sql = "UPDATE applications SET status = :s WHERE application_id = :aid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s' => $newStatus, ':aid' => $applicationId]);
        return $stmt->rowCount() === 1;
    }

    $sql = "
        UPDATE applications
        SET status = :s
        WHERE application_id = :aid
          AND job_id IN (
              SELECT job_id FROM jobs WHERE created_by_user_id = :uid
          )
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':s' => $newStatus, ':aid' => $applicationId, ':uid' => $currentUserId]);
    return $stmt->rowCount() === 1;
}

/**
 * Add a note to an application (only if recruiter owns the job or user is admin).
 */
function addApplicationNote(
    PDO $pdo,
    int $applicationId,
    int $userId,
    bool $isAdmin,
    string $content
): bool {
    if ($isAdmin) {
        $sql = "
            INSERT INTO notes (application_id, user_id, content, created_at)
            SELECT :appId, :userId, :content, CURRENT_TIMESTAMP
            WHERE EXISTS (
                SELECT 1 FROM applications WHERE application_id = :appId
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':appId'   => $applicationId,
            ':userId'  => $userId,
            ':content' => $content,
        ]);
        return $stmt->rowCount() === 1;
    }

    $sql = "
        INSERT INTO notes (application_id, user_id, content, created_at)
        SELECT :appId, :userId, :content, CURRENT_TIMESTAMP
        WHERE EXISTS (
            SELECT 1
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            WHERE a.application_id = :appId
              AND j.created_by_user_id = :ownerUid
        )
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':appId'    => $applicationId,
        ':userId'   => $userId,
        ':ownerUid' => $userId,
        ':content'  => $content,
    ]);
    return $stmt->rowCount() === 1;
}

/**
 * List all notes for an application, ordered by creation date.
 *
 * Caller is responsible for verifying that the user is allowed to see
 * this application (typically via getApplicationWithDocuments first).
 */
function listNotesForApplication(PDO $pdo, int $applicationId): array {
    $stmt = $pdo->prepare("
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
    $stmt->execute([':id' => $applicationId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}