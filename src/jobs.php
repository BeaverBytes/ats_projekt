<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const JOB_TITLE_MAX_LENGTH = 255;
const JOB_LOCATION_MAX_LENGTH = 150;

/**
 * Retrieve all publicly visible job postings.
 * 
 * Only jobs marked as active are returned to ensure that
 * unpublished or deactivated positions cannot be accessed 
 * via the public listing.
 * 
 * Results are ordered by creation date.
 */
function listActiveJobs(): array {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare(
        "SELECT job_id, title, location, created_at
        FROM jobs
        WHERE is_active = 1
        ORDER BY created_at DESC"
    );
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve a single active job posting by its identifier.
 * 
 * the additional is_active ckeck prevents direct access to unpublished jobs
 */
function findActiveJobById(int $jobId): ?array {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare(
        "SELECT job_id, title, description, location, created_at
        FROM jobs
        WHERE job_id = :id AND is_active = 1"
    );
    $stmt->execute([':id' => $jobId]);

    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    return $job !== false ? $job : null;
}

/**
 * Validate job input data for create/update operations.
 * 
 * Returns a list of error messages. An empty array means valid input.
 */
function validateJobInput(array $data): array {
    $errors = [];

    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $location = trim((string)($data['location'] ?? ''));

    // title: VARCHAR(255) NOT NULL
    if ($title === '' || mb_strlen($title) < 3) {
        $errors[] = 'Die Stellenbezeichnung ist erforderlich (mind. 3 Zeichen).';
    } elseif (mb_strlen($title) > JOB_TITLE_MAX_LENGTH) {
        $errors[] = 'Die Stellenbezeichnung darf maximal ' . JOB_TITLE_MAX_LENGTH . ' Zeichen lang sein.';
    }

    // description: TEXT NOT NULL
    if ($description === '' || mb_strlen($description) < 20) {
        $errors[] = 'Stellenbeschreibung ist erforderlich (mind. 20 Zeichen).';
    }

    // location: VARCHAR(150) optional
    if ($location !== '' && mb_strlen($location) > JOB_LOCATION_MAX_LENGTH) {
        $errors[] = 'Der Standort darf maximal ' . JOB_LOCATION_MAX_LENGTH . ' Zeichen lang sein.';
    }

    return $errors;
}

/**
 * Create a new job posting.
 * 
 * created_by_user_id is mandatory to ensure ownership and auditing.
 * is_active defaults to 1 (published) unless explicitly set to 0.
 * 
 * Returns the newly created job_id. 
 */
function createJob(int $createdByUserId, array $data): int {
    $pdo = getDatabaseConnection();

    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $location = trim((string)($data['location'] ?? ''));

    // Normalize checkbox input checked => 1, unchecked/missing => 0
    $isActive = isset($data['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare(
         "INSERT INTO jobs (title, description, location, is_active, created_by_user_id, created_at)
         VALUES (:title, :description, :location, :is_active, :created_by_user_id, datetime('now'))"
    );

    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':location' => $location,
        ':is_active' => $isActive,
        ':created_by_user_id' => $createdByUserId,
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Retrieve jobs created by a specific user.
 * 
 * Includes both active and inactive jobs for management purpose.
 */
function listJobsByCreator(int $userId): array {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare(
        "SELECT job_id, title, location, is_active, created_at
        FROM jobs
        WHERE created_by_user_id = :uid
        ORDER BY created_at DESC"
    );
    $stmt->execute([':uid' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve a job by ID without restricting is_active. 
 */
function findJobById(int $jobId): ?array {
    $pdo =getDatabaseConnection();

    $stmt = $pdo->prepare(
        "SELECT job_id, title, description, location, is_active, created_by_user_id, created_at
        FROM jobs
        WHERE job_id = :id"
    );
    $stmt->execute([':id' => $jobId]);

    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    return $job !== false ? $job : null;
}

/**
 * Change job visibility (active/inactive).
 * 
 * Security note: authorization (owner/admin) must be enforced by the caller
 * before calling this function.
 */
function setJobActive(int $jobId, int $isActive): void {
    $pdo = getDatabaseConnection();

    $isActive = ($isActive === 1) ? 1 : 0;

    $stmt = $pdo->prepare(
        "UPDATE jobs
        SET is_active = :active
        WHERE job_id = :id"
    );

    $stmt->execute([
        ':active' => $isActive,
        ':id' => $jobId,
    ]);
}

/**
 * Lists all jobs (admin view), including inactive jobs.
 */
function listAllJobs(): array {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query(
        "SELECT job_id, title, description, location, is_active, created_by_user_id, created_at
        FROM jobs
        ORDER BY created_at DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}