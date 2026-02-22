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
        <main class="container">

            <div class="card">
                <h1 class="page-title">Neue Stelle anlegen</h1>

                <!-- Success message -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Stelle erfolgreich erstellt.
                    </div>
                <?php endif; ?>

                <!-- Validation errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/jobs/index.php" class="btn btn-secondary">
                        ← Zurück zur Übersicht
                    </a>
                </div>

                <form method="post">

                    <div class="form-group">
                        <label for="title">Stellenbezeichnung*</label>
                        <input 
                            type="text"
                            id="title"
                            name="title"
                            value="<?= h($_POST['title'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="description">Beschreibung*</label>
                        <textarea 
                            id="description"
                            name="description"
                            required
                        ><?= h($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="location">Standort</label>
                        <input 
                            type="text"
                            id="location"
                            name="location"
                            value="<?= h($_POST['location'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group checkbox-row">
                        <input 
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            value="1"
                            <?= ($_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['is_active'])) ? 'checked' : '' ?>
                        >
                        <label for="is_active">Sofort veröffentlichen</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Stelle speichern
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </body>
</html>