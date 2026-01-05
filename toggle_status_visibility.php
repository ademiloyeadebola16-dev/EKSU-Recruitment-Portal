<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index'])) {

    $index  = (int)$_POST['index'];   // application ID
    $action = $_POST['action'] ?? '';

    // Determine visibility value
    if ($action === 'show') {
        $visible = 1;
        $_SESSION['message'] = "Applicant status is now visible to the applicant.";
    } elseif ($action === 'hide') {
        $visible = 0;
        $_SESSION['message'] = "Applicant status is now hidden from the applicant.";
    } else {
        header("Location: view_applications.php");
        exit();
    }

    // Update application
    $stmt = $pdo->prepare(
        "UPDATE applications SET status_visible = ? WHERE id = ?"
    );
    $stmt->execute([$visible, $index]);

    // Redirect back to applicant detail page
    header("Location: view_applicant_detail.php?index=" . $index);
    exit();
}

header("Location: view_applications.php");
exit();
