<?php
require 'db.php';

$jobs = json_decode(file_get_contents('jobs.json'), true);

$sql = "INSERT INTO jobs 
(title, faculty, department, position, description, requirement_qualification, requirement_experience, requirement_publications, requirement_body, deadline, status)
VALUES (?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $pdo->prepare($sql);

foreach ($jobs as $job) {
    $stmt->execute([
        $job['title'] ?? '',
        $job['faculty'] ?? '',
        $job['department'] ?? '',
        $job['position'] ?? '',
        $job['description'] ?? '',
        $job['requirement_qualification'] ?? '',
        (int)($job['requirement_experience'] ?? 0),
        (int)($job['requirement_publications'] ?? 0),
        $job['requirement_body'] ?? '',
        !empty($job['deadline']) ? $job['deadline'] : null,
        $job['status'] ?? 'active'
    ]);
}

echo "Jobs migrated successfully";
