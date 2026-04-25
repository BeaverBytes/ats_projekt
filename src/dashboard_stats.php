<?php
declare(strict_types=1);

/**
* KPI aggregation for dashboards
* - Admin dashboard: global KPIs
* - Recruiter dashboard: scoped by created_by_user_id
 */

/**
 * Compute KPIs for the admin dashboard: global counts across the system.
 */
function getAdminDashboardKpis(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
          (SELECT COUNT(*) FROM jobs WHERE is_active = 1) AS activeJobs,
          (SELECT COUNT(*) FROM jobs) AS allJobs,
          (SELECT COUNT(*) FROM applications) AS allApps,
          (SELECT COUNT(*) FROM applications WHERE status IN ('submitted','in_review','interview')) AS openApps,
          (SELECT COUNT(*) FROM applications WHERE status = 'offer') AS offers,
          (SELECT COUNT(*) FROM applications WHERE status = 'rejected') AS rejected,
          (SELECT COUNT(*) FROM users WHERE role = 'recruiter') AS recruiters
    ");

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [
            'activeJobs' => 0,
            'allJobs' => 0,
            'allApps' => 0,
            'openApps' => 0,
            'offers' => 0,
            'rejected' => 0,
            'recruiters' => 0,
        ];
    }

    return [
        'activeJobs'  => (int)$row['activeJobs'],
        'allJobs'     => (int)$row['allJobs'],
        'allApps'     => (int)$row['allApps'],
        'openApps'    => (int)$row['openApps'],
        'offers'      => (int)$row['offers'],
        'rejected'    => (int)$row['rejected'],
        'recruiters'  => (int)$row['recruiters'],
    ];
}

/**
 * Compute KPIs for the recruiter dashboard.
 *
 * - Admin: returns the same global counts as getAdminDashboardKpis().
 * - Recruiter: counts are scoped to jobs the user owns
 *   (jobs.created_by_user_id = $userId).
 */
function getRecruiterDashboardKpis(PDO $pdo, string $role, int $userId): array
{
    if ($role === 'admin') {
        $stmt = $pdo->query("
            SELECT
              (SELECT COUNT(*) FROM jobs WHERE is_active = 1) AS activeJobs,
              (SELECT COUNT(*) FROM applications) AS allApps,
              (SELECT COUNT(*) FROM applications WHERE status IN ('submitted','in_review','interview')) AS openApps,
              (SELECT COUNT(*) FROM applications WHERE status = 'offer') AS offers
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['activeJobs' => 0, 'allApps' => 0, 'openApps' => 0, 'offers' => 0];
        }

        return [
            'activeJobs' => (int)$row['activeJobs'],
            'allApps'    => (int)$row['allApps'],
            'openApps'   => (int)$row['openApps'],
            'offers'     => (int)$row['offers'],
        ];
    }

    // Recruiter path: all queries scoped to jobs the user owns
    $stmt = $pdo->prepare("
        SELECT
          (SELECT COUNT(*)
             FROM jobs
            WHERE is_active = 1
              AND created_by_user_id = :uid
          ) AS activeJobs,

          (SELECT COUNT(*)
             FROM applications a
             JOIN jobs j ON a.job_id = j.job_id
            WHERE j.created_by_user_id = :uid
          ) AS allApps,

          (SELECT COUNT(*)
             FROM applications a
             JOIN jobs j ON a.job_id = j.job_id
            WHERE j.created_by_user_id = :uid
              AND a.status IN ('submitted','in_review','interview')
          ) AS openApps,

          (SELECT COUNT(*)
             FROM applications a
             JOIN jobs j ON a.job_id = j.job_id
            WHERE j.created_by_user_id = :uid
              AND a.status = 'offer'
          ) AS offers
    ");

    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['activeJobs' => 0, 'allApps' => 0, 'openApps' => 0, 'offers' => 0];
    }

    return [
        'activeJobs' => (int)$row['activeJobs'],
        'allApps'    => (int)$row['allApps'],
        'openApps'   => (int)$row['openApps'],
        'offers'     => (int)$row['offers'],
    ];
}