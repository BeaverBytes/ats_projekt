<?php
/**
 * src/auth.php
 * Zentrales Authentifizierungs- und Autorisierungsmodul des ATS
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Startet eine Session, falls noch keine aktiv ist.
 function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Authentifiziert einen Benutzer über E-Mail und Passwort.
 * Bei Erfolg werden user_id und role in der Session gespeichert.
 */
function login(string $email, string $password): bool
{
    startSession();

    $email = trim($email);
    if ($email === '' || $password === '') {
        return false;
    }

    $pdo = getDatabaseConnection();

    // Prepared Statement zum Schutz vor SQL-Injections
    $stmt = $pdo->prepare(
        'SELECT user_id, password_hash, role
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);

    $user = $stmt->fetch();

    // Login Verifizierung
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Schutz vor Session Fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role'] = (string) $user['role'];

    return true;
}

// Loggt den Benutzer aus und beendet die Session
function logout(): void
{
    startSession();

    $_SESSION = [];

    // Session-Cookie invalidieren
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// Prüft ob gültige Benutzersession existiert
function isAuthenticated(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

// Liefert aktuelle User-ID zurück
function currentUserId(): ?int
{
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// Liefert Rolle des eingeloggten User zurück
function currentUserRole(): ?string
{
    startSession();
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
}

// Erzwingt Login: falls nicht eingeloggt, Redirect auf Login-Page
function requireAuth(string $loginPath = '/ats_projekt/public/login.php'): void
{
    if (!isAuthenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

// Erzwingt eine Rolle und gibt bei fehlender Berechtigung HTTP 403 zurück
function requireRole(string $role, string $loginPath = '/ats_projekt/public/login.php'): void
{
    requireAuth($loginPath);

    if (currentUserRole() !== $role) {
        http_response_code(403);
        exit('Access denied');
    }
}