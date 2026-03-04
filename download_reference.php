<?php
session_start();
require 'db.php';

// OPTIONAL: admin check
// if (!isset($_SESSION['admin'])) {
//     die("Unauthorized");
// }

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("SELECT file_path FROM referees WHERE id = ?");
$stmt->execute([$id]);
$ref = $stmt->fetch();

if (!$ref || empty($ref['file_path'])) {
    die("File not found.");
}

$file = $ref['file_path'];

if (!file_exists($file)) {
    die("File missing.");
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reference.pdf"');
readfile($file);
exit;
