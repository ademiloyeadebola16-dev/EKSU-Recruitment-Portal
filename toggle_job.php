<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ------------------ GET JOB ID ------------------ */
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
    header("Location: admin.php");
    exit();
}

/* ------------------ FETCH JOB ------------------ */
$stmt = $pdo->prepare("
    SELECT deadline, is_active
    FROM jobs
    WHERE id = ?
");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header("Location: admin.php");
    exit();
}

/* ------------------ CHECK EXPIRY ------------------ */
$isExpired = !empty($job['deadline']) &&
             strtotime($job['deadline']) < time();

/* ------------------ TOGGLE ------------------ */
if (!$isExpired) {
    $newStatus = $job['is_active'] ? 0 : 1;

    $stmt = $pdo->prepare("
        UPDATE jobs
        SET is_active = ?
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $jobId]);
}

header("Location: admin.php");
exit();
