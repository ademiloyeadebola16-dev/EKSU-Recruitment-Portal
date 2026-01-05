<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    /* ---------------- FETCH APPLICATION ---------------- */
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, status_visible
        FROM applications
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {

        /* ---------------- UPDATE STATUS VISIBILITY ---------------- */
        $stmt = $pdo->prepare("
            UPDATE applications
            SET status_visible = 1
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        $name = trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''));
        $_SESSION['message'] = "Status is now visible to the applicant: {$name}";

    } else {
        $_SESSION['message'] = "Invalid applicant ID.";
    }

    header("Location: view_applications.php");
    exit();
}
?>
