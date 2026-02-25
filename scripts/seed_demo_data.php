<?php
declare(strict_types=1);

/**
 * Seeds demo jobs, applications and demo PDF documents (German texts).
 *
 * CLI-only script. Safe to run multiple times (idempotent-ish):
 * - Jobs: identified by (title + created_by_user_id)
 * - Applications: identified by (job_id + applicant_email)
 * - Documents: identified by (application_id + original_filename)
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../src/db.php';

try {
    $pdo = getDatabaseConnection();

    // Ensure uploads directory exists
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0775, true);
    }

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
            'doc_original' => 'Lebenslauf_Lena_Schneider.pdf',
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
            'doc_original' => 'Lebenslauf_Tobias_Wagner.pdf',
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
            'doc_original' => 'Lebenslauf_Mira_Klein.pdf',
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
            'doc_original' => 'Lebenslauf_Jonas_Becker.pdf',
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
            'doc_original' => 'Lebenslauf_Sofia_Meyer.pdf',
        ],

        // recruiter1 - inactive support job (still useful for admin demo)
        [
            'job_key' => 'recruiter1@example.com|IT-Support (m/w/d) – 1st Level',
            'first' => 'Paul',
            'last' => 'Neumann',
            'email' => 'paul.neumann@example.com',
            'phone' => '',
            'motivation' => "Ich habe bereits im 1st Level Support gearbeitet, kenne Ticket-Systeme und kann komplexe Probleme verständlich erklären. Ich bin zuverlässig und arbeite gerne im Team.",
            'status' => 'submitted',
            'consent' => 1,
            'doc_original' => 'Lebenslauf_Paul_Neumann.pdf',
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

    // Seed documents (1 demo PDF per application) 
    $stmtFindDoc = $pdo->prepare("
        SELECT document_id
        FROM documents
        WHERE application_id = :appId
          AND original_filename = :original
        LIMIT 1
    ");

    $stmtInsertDoc = $pdo->prepare("
        INSERT INTO documents (
            application_id,
            original_filename,
            stored_filename,
            mime_type,
            size_bytes,
            uploaded_at
        )
        VALUES (
            :appId,
            :original,
            :stored,
            :mime,
            :size,
            CURRENT_TIMESTAMP
        )
    ");

    // Minimal demo PDF content (good enough for downloads and file presence)
    $pdfContent = "%PDF-1.4
                    1 0 obj
                    << /Type /Catalog /Pages 2 0 R >>
                    endobj
                    2 0 obj
                    << /Type /Pages /Kids [3 0 R] /Count 1 >>
                    endobj
                    3 0 obj
                    << /Type /Page /Parent 2 0 R /MediaBox [0 0 300 144]
                    /Contents 4 0 R
                    /Resources << /Font << /F1 5 0 R >> >> >>
                    endobj
                    4 0 obj
                    << /Length 44 >>
                    stream
                    BT
                    /F1 12 Tf
                    72 100 Td
                    (Demo Bewerbung) Tj
                    ET
                    endstream
                    endobj
                    5 0 obj
                    << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
                    endobj
                    xref
                    0 6
                    0000000000 65535 f 
                    trailer
                    << /Root 1 0 R /Size 6 >>
                    startxref
                    0
                    %%EOF";

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

        // Create or reuse application
        $stmtFindApp->execute([
            ':job_id' => $jobId,
            ':email'  => $app['email'],
        ]);

        $existingAppId = $stmtFindApp->fetchColumn();
        if ($existingAppId) {
            $applicationId = (int)$existingAppId;
            echo "Application already exists (skipping): {$app['email']}\n";
        } else {
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

            $applicationId = (int)$pdo->lastInsertId();
            echo "Application created: {$app['email']} ({$app['status']})\n";
        }

        // Create demo document if missing (bound via documents.application_id)
        $originalFilename = (string)($app['doc_original'] ?? 'Lebenslauf.pdf');

        $stmtFindDoc->execute([
            ':appId'    => $applicationId,
            ':original' => $originalFilename,
        ]);

        if ($stmtFindDoc->fetchColumn()) {
            echo "Document already exists (skipping): {$originalFilename} for app #{$applicationId}\n";
            continue;
        }

        $storedFilename = bin2hex(random_bytes(16)) . '.pdf';
        $filePath = $uploadsDir . '/' . $storedFilename;

        file_put_contents($filePath, $pdfContent, LOCK_EX);

        $stmtInsertDoc->execute([
            ':appId'    => $applicationId,
            ':original' => $originalFilename,
            ':stored'   => $storedFilename,
            ':mime'     => 'application/pdf',
            ':size'     => strlen($pdfContent),
        ]);

        echo "Document created: {$originalFilename} (stored: {$storedFilename}) for app #{$applicationId}\n";
    }

    echo "Demo data seeding finished.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}