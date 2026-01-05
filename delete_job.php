<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jobId = (int)($_POST['id'] ?? 0);

    if ($jobId > 0) {

        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$jobId]);

        $_SESSION['message'] = "Job deleted successfully!";
    }

    header("Location: admin.php");
    exit();
}
