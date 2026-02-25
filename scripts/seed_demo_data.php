<?php
declare(strict_types=1);

/**
 * Seeds demo jobs and applications (German texts).
 *
 * CLI-only script. Safe to run multiple times (idempotent-ish).
 * - Requires that users already exist (admin + recruiters)
 * - Uses recruiter emails to resolve user_id
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../src/db.php';

try {
    $pdo = getDatabaseConnection();

    // Resolve recruiter user IDs by email (must exist from user seeder)
    $recruiterEmails = [
        'recruiter1@example.com',
        'recruiter2@example.com',
    ];

    $recruiterIds = [];
    $stmtUser = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");

    foreach ($recruiterEmails as $email) {
        $stmtUser->execute(['email' => $email]);
        $uid = $stmtUser->fetchColumn();

        if (!$uid) {
            echo "Missing recruiter user: {$email}. Run user seeder first.\n";
            exit(1);
        }

        $recruiterIds[$email] = (int)$uid;
    }

    // --- Seed jobs (German texts) ---
    $jobs = [
        [
            'created_by' => 'recruiter1@example.com',
            'title' => 'Webentwickler:in (PHP) – Junior',
            'description' => 'Du unterstützt unser Team bei der Weiterentwicklung interner Webanwendungen in PHP. Du arbeitest eng mit Recruiting und Fachbereich zusammen und lernst Clean Code, Testing und sichere Upload-Workflows kennen.',
            'location' => 'Berlin',
            'is_active' => 1,
        ],
        [
            'created_by' => 'recruiter1@example.com',
            'title' => 'IT-Support (m/w/d) – 1st Level',
            'description' => 'Du bist erste Ansprechperson bei IT-Fragen, dokumentierst Tickets und unterstützt bei der Einrichtung von Endgeräten. Erfahrung mit Windows und grundlegenden Netzwerkkenntnissen sind von Vorteil.',
            'location' => 'Leipzig',
            'is_active' => 0, // intentionally inactive
        ],
        [
            'created_by' => 'recruiter2@example.com',
            'title' => 'Frontend Developer (m/w/d) – HTML/CSS',
            'description' => 'Du erstellst und pflegst UI-Komponenten mit HTML und CSS. Fokus auf sauberes Markup, Barrierefreiheit und konsistente Styles. JavaScript ist ein Plus, aber kein Muss.',
            'location' => 'Remote',
            'is_active' => 1,
        ],
    ];

    $stmtFindJob = $pdo->prepare("
        SELECT job_id
        FROM jobs
        WHERE title = :title
          AND created_by_user_id = :uid
        LIMIT 1
    ");

    $stmtInsertJob = $pdo->prepare("
        INSERT INTO jobs (title, description, location, is_active, created_by_user_id, created_at)
        VALUES (:title, :description, :location, :is_active, :uid, CURRENT_TIMESTAMP)
    ");

    $jobIdsByKey = []; // key = created_by_email|title => job_id

    foreach ($jobs as $job) {
        $uid = $recruiterIds[$job['created_by']];

        $stmtFindJob->execute([
            ':title' => $job['title'],
            ':uid'   => $uid,
        ]);

        $existingJobId = $stmtFindJob->fetchColumn();
        if ($existingJobId) {
            $jobId = (int)$existingJobId;
            echo "Job already exists (skipping): {$job['title']} ({$job['created_by']})\n";
        } else {
            $stmtInsertJob->execute([
                ':title'       => $job['title'],
                ':description' => $job['description'],
                ':location'    => $job['location'],
                ':is_active'   => $job['is_active'],
                ':uid'         => $uid,
            ]);

            $jobId = (int)$pdo->lastInsertId();
            echo "Job created: {$job['title']} ({$job['created_by']})\n";
        }

        $jobIdsByKey[$job['created_by'] . '|' . $job['title']] = $jobId;
    }

    // --- Seed applications (German texts) ---
    // We identify an application as existing by (job_id + applicant_email).
    $applications = [
        // recruiter1 - PHP job
        [
            'job_key' => 'recruiter1@example.com|Webentwickler:in (PHP) – Junior',
            'first' => 'Lena',
            'last' => 'Schneider',
            'email' => 'lena.schneider@example.com',
            'phone' => '0151 23456789',
            'motivation' => "Ich möchte als Junior PHP-Entwicklerin in einem Team arbeiten, das Wert auf saubere Architektur und Sicherheit legt. Besonders interessiert mich der Umgang mit Uploads, Sessions und Datenbanken im MVP-Kontext.",
            'status' => 'submitted',
            'consent' => 1,
        ],
        [
            'job_key' => 'recruiter1@example.com|Webentwickler:in (PHP) – Junior',
            'first' => 'Tobias',
            'last' => 'Wagner',
            'email' => 'tobias.wagner@example.com',
            'phone' => '',
            'motivation' => "Ich habe bereits kleinere PHP-Projekte umgesetzt und möchte mich im Bereich Websecurity (XSS/CSRF/SQLi) weiterentwickeln. Der ausgeschriebene Job passt sehr gut zu meinem Profil.",
            'status' => 'in_review',
            'consent' => 1,
        ],
        [
            'job_key' => 'recruiter1@example.com|Webentwickler:in (PHP) – Junior',
            'first' => 'Mira',
            'last' => 'Klein',
            'email' => 'mira.klein@example.com',
            'phone' => '0176 11112222',
            'motivation' => "Ich freue mich auf ein strukturiertes Bewerbungsgespräch und möchte meine Kenntnisse in PHP 8, PDO und HTML/CSS einbringen. Außerdem interessiere ich mich für Testing und saubere Code-Reviews.",
            'status' => 'interview',
            'consent' => 1,
        ],

        // recruiter2 - Frontend job
        [
            'job_key' => 'recruiter2@example.com|Frontend Developer (m/w/d) – HTML/CSS',
            'first' => 'Jonas',
            'last' => 'Becker',
            'email' => 'jonas.becker@example.com',
            'phone' => '',
            'motivation' => "Ich arbeite gerne an klaren, zugänglichen UIs. Besonders wichtig sind mir konsistente Styles, verständliche Komponenten und eine gute Nutzerführung im Recruiting-Prozess.",
            'status' => 'offer',
            'consent' => 1,
        ],
        [
            'job_key' => 'recruiter2@example.com|Frontend Developer (m/w/d) – HTML/CSS',
            'first' => 'Sofia',
            'last' => 'Meyer',
            'email' => 'sofia.meyer@example.com',
            'phone' => '0160 55556666',
            'motivation' => "Ich habe Erfahrung mit HTML/CSS und möchte mich stärker in Richtung Accessibility und Design-Systeme entwickeln. Die Stelle passt sehr gut, weil ich gerne strukturiert und sauber arbeite.",
            'status' => 'rejected',
            'consent' => 1,
        ],

        // recruiter1 - inactive support job (still can have applications in DB for demo/admin views)
        [
            'job_key' => 'recruiter1@example.com|IT-Support (m/w/d) – 1st Level',
            'first' => 'Paul',
            'last' => 'Neumann',
            'email' => 'paul.neumann@example.com',
            'phone' => '',
            'motivation' => "Ich habe bereits im 1st Level Support gearbeitet, kenne Ticket-Systeme und kann komplexe Probleme verständlich erklären. Ich bin zuverlässig und arbeite gerne im Team.",
            'status' => 'submitted',
            'consent' => 1,
        ],
    ];

    $allowedStatuses = ['submitted', 'in_review', 'interview', 'offer', 'rejected'];

    $stmtFindApp = $pdo->prepare("
        SELECT application_id
        FROM applications
        WHERE job_id = :job_id
          AND applicant_email = :email
        LIMIT 1
    ");

    $stmtInsertApp = $pdo->prepare("
        INSERT INTO applications (
            job_id,
            applicant_first_name,
            applicant_last_name,
            applicant_email,
            applicant_phone,
            motivation,
            status,
            consent,
            created_at
        )
        VALUES (
            :job_id,
            :first,
            :last,
            :email,
            :phone,
            :motivation,
            :status,
            :consent,
            CURRENT_TIMESTAMP
        )
    ");

    foreach ($applications as $app) {
        $jobId = $jobIdsByKey[$app['job_key']] ?? 0;
        if ($jobId <= 0) {
            echo "Missing job for key: {$app['job_key']} (skipping application)\n";
            continue;
        }

        if (!in_array($app['status'], $allowedStatuses, true)) {
            echo "Invalid status '{$app['status']}' for {$app['email']} (skipping)\n";
            continue;
        }

        $stmtFindApp->execute([
            ':job_id' => $jobId,
            ':email'  => $app['email'],
        ]);

        if ($stmtFindApp->fetchColumn()) {
            echo "Application already exists (skipping): {$app['email']}\n";
            continue;
        }

        $stmtInsertApp->execute([
            ':job_id'     => $jobId,
            ':first'      => $app['first'],
            ':last'       => $app['last'],
            ':email'      => $app['email'],
            ':phone'      => $app['phone'],
            ':motivation' => $app['motivation'],
            ':status'     => $app['status'],
            ':consent'    => $app['consent'],
        ]);

        echo "Application created: {$app['email']} ({$app['status']})\n";
    }

    echo "Demo data seeding finished.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}