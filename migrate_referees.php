<?php
require 'db.php';

/**
 * Path to old JSON applications file
 */
$jsonFile = __DIR__ . '/applications.json';

if (!file_exists($jsonFile)) {
    die("applications.json not found.");
}

$applications = json_decode(file_get_contents($jsonFile), true);

if (!is_array($applications)) {
    die("Invalid JSON format.");
}

$findAppStmt = $pdo->prepare("
    SELECT id FROM applications
    WHERE email = ? AND job_id = ?
    LIMIT 1
");

$insertRefStmt = $pdo->prepare("
    INSERT INTO referees (application_id, name, occupation, email, phone)
    VALUES (?, ?, ?, ?, ?)
");

$migrated = 0;

foreach ($applications as $app) {

    if (empty($app['referees']) || !is_array($app['referees'])) {
        continue;
    }

    // Find application in MySQL
    $findAppStmt->execute([
        $app['email'] ?? '',
        $app['job_id'] ?? 0
    ]);

    $applicationId = $findAppStmt->fetchColumn();

    if (!$applicationId) {
        continue; // Application not found in DB
    }

    foreach ($app['referees'] as $ref) {
        if (empty($ref['name'])) continue;

        $insertRefStmt->execute([
            $applicationId,
            trim($ref['name']),
            trim($ref['occupation'] ?? ''),
            trim($ref['email'] ?? ''),
            trim($ref['phone'] ?? '')
        ]);

        $migrated++;
    }
}

echo "✅ Migration complete. Referees migrated: {$migrated}";
