<?php
declare(strict_types=1);

/**
 * Recruiter dashboard.
 *
 * Access restricted to users with role "recruiter".
 */
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';

// Enforce role
requireAnyRole(['admin', 'recruiter']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recruiter Dashboard – ATS</title>
</head>
<body>
    <h1>Recruiter Dashboard</h1>
    <p>Du bist eingeloggt (recruiter).</p>
    <p><a href="<?= BASE_PATH ?>/logout.php">Logout</a></p>
</body>
</html>