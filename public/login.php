<?php
/**
 * public/login.php 
 * HTTP-Entry-Point für die Authentifizierung von Recruitern und Administratoren.
 * Die Datei übernimmt die Verarbeitung eingehender POST-Requests und ruft
 * die Authentifizierungsfunktionen src/auth.php Moduls auf.
 */
declare(strict_types=1);

// Einbindung des zentralen Authentifizierungsmoduls
require_once __DIR__ . '/../src/auth.php';

// Session initialisieren
startSession();

// Variable für Fehlermeldung
$error = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string)($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Rollenbasierte Weiterleitung
    if (login($email, $password)) {

        $role = currentUserRole();

        if ($role === 'admin') {
            header('Location: /ats_projekt/public/admin/dashboard.php');
            exit;
        }

        header('Location: /ats_projekt/public/recruiter/dashboard.php');
        exit;
    }

    // Generische Fehlermeldung zum Schutz vor Enumeration
    $error = 'Login fehlgeschlagen. Bitte E-Mail und Passwort prüfen.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login – ATS</title>
</head>
<body>
    <h1>Login</h1>

    <?php if ($error): ?>
        <!-- 
            Ausgabe der Fehlermeldung.
            htmlspecialchars verhindert Cross-Site-Scripting (XSS),
            falls manipulierte Eingaben zurückgegeben würden.
        -->
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- Login-Formular -->
    <form method="post" action="">
        <div>
            <label for="email">E-Mail</label><br>
            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="username"
                value="<?php 
                    // Wiederbefüllung des Email-Feldes bei Fehler zur erhöhung der Usability
                    echo htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <div style="margin-top: 8px;">
            <label for="password">Passwort</label><br>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </div>

        <div style="margin-top: 12px;">
            <button type="submit">Anmelden</button>
        </div>
    </form>
</body>
</html>