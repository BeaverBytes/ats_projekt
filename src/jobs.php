<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function listActiveJobs(): array
{
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

function findActiveJobById(int $jobId): ?array
{
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare(
        "SELECT job_id, title, description, location, created_at
        FROM jobs
        WHERE job_id = :id AND is_active = 1
        LIMIT 1"
    );
    $stmt->execute([':id => $jobId']);

    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    return $job !== false ? $job : null;
}