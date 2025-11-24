<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $company = trim($_POST['company']);
    $location = trim($_POST['location']);
    $qualification = trim($_POST['qualification']);
    $description = trim($_POST['description']);

    $file = 'jobs.json';
    $jobs = json_decode(file_get_contents($file), true);

    if (isset($jobs[$id])) {
        $jobs[$id] = [
            'title' => $title,
            'company' => $company,
            'location' => $location,
            'qualification' => $qualification,
            'description' => $description
        ];

        file_put_contents($file, json_encode($jobs, JSON_PRETTY_PRINT));
        $_SESSION['message'] = "Job updated successfully!";
    }

    header("Location: admin.php");
    exit();
}
?>
