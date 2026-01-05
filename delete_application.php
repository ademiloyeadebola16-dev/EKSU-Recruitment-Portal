<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['index'])) {
    $_SESSION['message'] = "Invalid request.";
    header("Location: view_applications.php");
    exit();
}

$applicationId = (int)$_GET['index'];

// Fetch application first (for name + validation)
$stmt = $pdo->prepare("SELECT first_name, last_name FROM applications WHERE id = ? LIMIT 1");
$stmt->execute([$applicationId]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    $_SESSION['message'] = "Application not found.";
    header("Location: view_applications.php");
    exit();
}

// Delete application
$delete = $pdo->prepare("DELETE FROM applications WHERE id = ?");
$delete->execute([$applicationId]);

$_SESSION['message'] = "Application for {$app['first_name']} {$app['last_name']} deleted successfully.";
header("Location: view_applications.php");
exit();

?>
