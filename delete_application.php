<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['index'])) {
    $_SESSION['message'] = "Invalid request.";
    header("Location: view_applications.php");
    exit();
}

$applications_file = 'applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

$index = (int)$_GET['index'];

if (!isset($applications[$index])) {
    $_SESSION['message'] = "Application not found.";
    header("Location: view_applications.php");
    exit();
}

// Remove the application
$deletedApp = $applications[$index];
unset($applications[$index]);

// Reindex array to avoid gaps in JSON
$applications = array_values($applications);

// Save back to JSON
file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

$_SESSION['message'] = "Application for {$deletedApp['first_name']} {$deletedApp['last_name']} deleted successfully.";
header("Location: view_applications.php");
exit();
?>
