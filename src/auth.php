<?php
declare(strict_types=1);

/**
 * Central authentication and authorization module of the ATS.
 *
 * Handles session management, login/logout logic,
 * and role-based access control (RBAC).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Ensure session is active
function startSession(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Authenticate user by email and password.
 * Stores user_id and role in session on success.
 */
function login(string $email, string $password): bool {
    startSession();

    $email = trim($email);
    if ($email === '' || $password === '') {
        return false;
    }

    $pdo = getDatabaseConnection();

    // Prevent SQL injection via prepared statement
    $stmt = $pdo->prepare(
        'SELECT user_id, password_hash, role
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password hash
    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        return false;
    }

    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['role'] = (string)$user['role'];

    return true;
}

// Destroy session and remove authentication data
function logout(): void {
    startSession();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }

    session_destroy();
}

// Check if user is authenticated
function isAuthenticated(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

// Get current user ID
function currentUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// Get current user role
function currentUserRole(): ?string {
    startSession();
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
}

// Require login, otherwise redirect
function requireAuth(string $loginPath = BASE_PATH . '/login.php'): void {
    if (!isAuthenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

// Require specific role, otherwise 403
function requireRole(string $role, string $loginPath = BASE_PATH . '/login.php'): void {
    requireAuth($loginPath);

    if (currentUserRole() !== $role) {
        http_response_code(403);
        exit('Access denied');
    }
}

// Require one of multiple roles
function requireAnyRole(array $roles, string $loginPath = BASE_PATH . '/login.php'): void {
    requireAuth($loginPath);

    if (!in_array(currentUserRole(), $roles, true)) {
        http_response_code(403);
        exit('Access denied');
    }
}

/**
 * CSRF protection (minimal).
 * - Token is stored in session
 * - Verified on POST requests
 */

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    $t = htmlspecialchars(csrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

function csrfVerify(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken  = $_POST['csrf_token'] ?? '';

    if (!is_string($sessionToken) || !is_string($postedToken)) {
        http_response_code(400);
        exit('Ungültige Anfrage (CSRF).');
    }

    if ($sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(400);
        exit('Ungültige Anfrage (CSRF).');
    }
}