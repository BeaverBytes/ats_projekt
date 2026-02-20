<?php
/**
 * public/admin/dashboard.php 
 * 
 * Zugriff ausschließlich mit Rolle "admin"
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';

requireRole('admin');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard – ATS</title>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p>Du bist eingeloggt (admin).</p>
    <p><a href="/ats_projekt/public/logout.php">Logout</a></p>
</body>
</html>