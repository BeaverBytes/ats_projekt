<?php
declare(strict_types=1);

/**
 * Logout entry point.
 *
 * Destroys the current session and redirects to login.
 */
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';

// Destroy session and authentication data
logout();

// Redirect to login page
header('Location: ' . BASE_PATH . '/login.php');
exit;