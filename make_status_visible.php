<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$applications_file = 'applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

if (isset($_GET['index'])) {
    $index = intval($_GET['index']);

    if (isset($applications[$index])) {

        // Ensure status_visible field always exists
        $applications[$index]['status_visible'] = true;

        file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

        $name = ($applications[$index]['first_name'] ?? '') . " " . ($applications[$index]['last_name'] ?? '');
        $_SESSION['message'] = "Status is now visible to the applicant: {$name}";
    } else {
        $_SESSION['message'] = "Invalid applicant index.";
    }

    header("Location: view_applications.php");
    exit();
}
?>
