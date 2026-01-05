<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin']['email'])) {
    $_SESSION['message'] = "Only Super Admin can approve admin accounts.";
    header("Location: admin.php");
    exit();
}

$currentEmail = $_SESSION['admin']['email'];

$stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
$stmt->execute([$currentEmail]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

// Only super admin can approve
if (!$current || $current['role'] !== 'super') {
    $_SESSION['message'] = "Only Super Admin can approve admin accounts.";
    header("Location: admin.php");
    exit();
}


if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Invalid request: no admin ID supplied.";
    header("Location: admin.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['message'] = "Admin not found.";
    header("Location: admin.php");
    exit();
}

// Prevent approving another Super Admin
if ($admin['role'] === 'super') {
    $_SESSION['message'] = "Cannot approve another Super Admin.";
    header("Location: admin.php");
    exit();
}

// Approve admin
$update = $pdo->prepare("UPDATE admins SET approved = 1 WHERE id = ?");
$update->execute([$id]);

$_SESSION['message'] = "Admin account approved successfully.";
header("Location: admin.php");
exit();

