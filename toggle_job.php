<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$jobs_file = 'jobs.json';
$jobs = json_decode(file_get_contents($jobs_file), true) ?? [];

$id = (int)($_GET['id'] ?? -1);

if (isset($jobs[$id])) {
    $isExpired = !empty($jobs[$id]['deadline']) &&
                 strtotime($jobs[$id]['deadline']) < time();

    if (!$isExpired) {
        $jobs[$id]['is_active'] = empty($jobs[$id]['is_active']);
        file_put_contents($jobs_file, json_encode(array_values($jobs), JSON_PRETTY_PRINT));
    }
}

header("Location: admin.php");
exit();
