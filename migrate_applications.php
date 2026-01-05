<?php
require 'db.php';

$applications = json_decode(file_get_contents('applications.json'), true);

$sql = "INSERT INTO applications 
(applicant_number, first_name, middle_name, last_name, email, phone, job_title, qualification, experience, publications, status)
VALUES (?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $pdo->prepare($sql);

foreach ($applications as $app) {
    $stmt->execute([
        $app['applicant_number'] ?? null,
        $app['first_name'] ?? '',
        $app['middle_name'] ?? '',
        $app['last_name'] ?? '',
        $app['email'] ?? '',
        $app['phone'] ?? '',
        $app['job_title'] ?? '',
        $app['qualification'] ?? '',
        (int)($app['experience'] ?? 0),
        (int)($app['publications'] ?? 0),
        $app['status'] ?? 'Pending'
    ]);
}

echo "Applications migrated successfully";
