<?php
declare(strict_types=1);

/**
 * Recruiter job creation page.
 * Renders form and handles job persistance
 */

require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/jobs.php';

// Restrict access to admin and recruiter roles
requireAnyRole(['admin', 'recruiter']);

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate user input
    $errors = validateJobInput($_POST);

    // If validation passes, create job entry
    if (empty($errors)) {
        createJob(currentUserId(), $_POST);

        // Redirect to avoid form resubmission (PRG pattern)
        header('Location: ' . BASE_PATH . '/recruiter/jobs/new.php?success=1');
        exit;
    }
}

// Escape helper to prevent XSS in output.
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="de">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neue Stelle anlegen – ATS</title>

    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
</head>
    <body>
        <h1>Neue Stelle anlegen</h1>

        <!-- Success message after redirect --> 
         <?php if (isset($_GET['success'])): ?>
            <p style="color: green;">Stelle erfolgreich erstellt.</p>
        <?php endif; ?>

        <!-- Display validation errors --> 
        <?php if (!empty($errors)): ?>
            <ul style="color: red;">
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                    <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="form-actions">
            <a href="<?= BASE_PATH ?>/jobs/index.php" class="btn btn-secondary">
                ← Zurück zur Übersicht
            </a>
        </div>

        <form method="post">

        <!-- Job title --> 
        <div>
            <label for="title">Stellenbezeichnung*</label>
            <input 
                type="text"
                id="title"
                name="title"
                value="<?= h($_POST['title'] ?? '') ?>"
                required
            >
        </div>

        <!-- Job description -->
        <div>
            <label for="description">Beschreibung*</label>
            <textarea 
                id="description"
                name="description"
                rows="6"
                required
            ><?= h($_POST['description'] ?? '') ?></textarea>
        </div>

        <!-- Optional job location -->
        <div>
            <label for="location">Standort</label>
            <input 
                type="text"
                id="location"
                name="location"
                value="<?= h($_POST['location'] ?? '') ?>"
            >
        </div>

       <!-- Publish toggle -->
        <div>
            <label>
                <input 
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?= ($_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['is_active'])) ? 'checked' : '' ?>
                >
                Sofort veröffentlichen
            </label>
        </div>

        <!-- Submit button -->
        <div>
            <button type="submit">Stelle speichern</button>
        </div>
        </form>
    </body>
</html>