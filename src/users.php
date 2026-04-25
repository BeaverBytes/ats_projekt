<?php
declare(strict_types=1);

/**
 * User-related DB queries.
 */

/**
 * Returns all users with role 'recruiter', ordered by email.
 *
 * Used in the admin filter dropdown on the application list.
 */
function listRecruiters(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT user_id, email
        FROM users
        WHERE role = 'recruiter'
        ORDER BY email ASC
    ");
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // normalize types
    foreach ($rows as &$r) {
        $r['user_id'] = (int)$r['user_id'];
        $r['email']   = (string)$r['email'];
    }

    return $rows;
}