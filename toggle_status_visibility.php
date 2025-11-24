<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index'])) {

    $applications_file = 'applications.json';
    $applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

    $index = intval($_POST['index']);
    $action = $_POST['action'] ?? '';  // NEW

    if (isset($applications[$index])) {

        // Toggle visibility based on action
        if ($action === 'show') {
            $applications[$index]['status_visible'] = true;
            $_SESSION['message'] = "Applicant status is now visible to the applicant.";
        } 
        elseif ($action === 'hide') {
            $applications[$index]['status_visible'] = false;
            $_SESSION['message'] = "Applicant status is now hidden from the applicant.";
        }

        file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));
    }

    // Redirect back to the applicant detail page
    header("Location: view_applicant_detail.php?index=" . $index);
    exit();
}

header("Location: view_applications.php");
exit();
?>
