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

/**
 * Start the PHP session if it is not already active.
 *
 * Idempotent: safe to call multiple times per request.
 */
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

    // Prepared statement: parameterized query, no string concatenation
    $stmt = $pdo->prepare(
        'SELECT user_id, password_hash, role
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // password_verify uses a constant-time comparison internally
    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        return false;
    }

    // Session fixation defense: assign a fresh session ID after successful auth
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['role'] = (string)$user['role'];

    return true;
}

/**
 * Destroy the current session and clear authentication data.
 */
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

/**
 * Returns true if the current session belongs to a logged-in user.
 */
function isAuthenticated(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Returns the user_id of the currently logged-in user, or null if anonymous.
 */
function currentUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Returns the role ('admin' or 'recruiter') of the logged-in user,
 * or null if anonymous.
 */
function currentUserRole(): ?string {
    startSession();
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
}

/**
 * Require an authenticated session. Redirects to login on failure.
 */
function requireAuth(string $loginPath = BASE_PATH . '/login.php'): void {
    if (!isAuthenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Require an authenticated session with the given role.
 *
 * Internally calls requireAuth() first, then checks the role.
 * Sends 403 if the role does not match.
 */
function requireRole(string $role, string $loginPath = BASE_PATH . '/login.php'): void {
    requireAuth($loginPath);

    if (currentUserRole() !== $role) {
        http_response_code(403);
        exit('Access denied');
    }
}

/**
 * Require an authenticated session with one of the given roles.
 *
 * Internally calls requireAuth() first, then checks the role list.
 * Sends 403 if no role matches.
 */
function requireAnyRole(array $roles, string $loginPath = BASE_PATH . '/login.php'): void {
    requireAuth($loginPath);

    if (!in_array(currentUserRole(), $roles, true)) {
        http_response_code(403);
        exit('Access denied');
    }
}

/**
 * CSRF protection (minimal).
 *
 * - Token is stored in session, regenerated on first access
 * - Validated on every state-changing POST request
 * - Comparison via hash_equals() to avoid timing leaks
 */

/**
 * Returns the CSRF token for the current session, generating it on first use.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Returns a hidden HTML input element containing the CSRF token,
 * ready to be embedded in a POST form.
 */
function csrfField(): string
{
    $t = htmlspecialchars(csrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

/**
 * Verifies the CSRF token from $_POST against the session token.
 * Aborts the request with HTTP 400 if the token is missing or invalid.
 */
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