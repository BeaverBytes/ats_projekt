<?php
/**
 * public/recruiter/dashboard.php
 *
 * Zugriff ausschließlich mit Rolle 'recruiter'.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';

requireRole('recruiter');
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
    <p><a href="/ats_projekt/public/logout.php">Logout</a></p>
</body>
</html>