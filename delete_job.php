<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $file = 'jobs.json';
    $jobs = json_decode(file_get_contents($file), true);

    if (isset($jobs[$id])) {
        array_splice($jobs, $id, 1);
        file_put_contents($file, json_encode($jobs, JSON_PRETTY_PRINT));
        $_SESSION['message'] = "Job deleted successfully!";
    }

    header("Location: admin.php");
    exit();
}
?>
