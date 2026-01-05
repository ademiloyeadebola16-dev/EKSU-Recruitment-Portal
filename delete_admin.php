<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

$currentAdmin = $_SESSION['admin'];

/* ------------------ SUPER ADMIN CHECK ------------------ */
if (strtolower($currentAdmin['role']) !== 'super_admin') {
    $_SESSION['message'] = "Only Super Admin can delete admins.";
    header("Location: admin.php");
    exit();
}

/* ------------------ TARGET ADMIN ID ------------------ */
$adminId = (int)($_POST['id'] ?? 0);

if ($adminId <= 0) {
    $_SESSION['message'] = "Invalid admin selected.";
    header("Location: admin.php");
    exit();
}

/* ------------------ FETCH TARGET ------------------ */
$stmt = $pdo->prepare("SELECT id, email, role FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    $_SESSION['message'] = "Admin not found.";
    header("Location: admin.php");
    exit();
}

/* ------------------ SAFETY RULES ------------------ */
if ($target['role'] === 'super_admin') {
    $_SESSION['message'] = "You cannot delete a Super Admin.";
    header("Location: admin.php");
    exit();
}

if ($target['id'] == $currentAdmin['id']) {
    $_SESSION['message'] = "You cannot delete yourself.";
    header("Location: admin.php");
    exit();
}

/* ------------------ SOFT DELETE ------------------ */
$stmt = $pdo->prepare("
    UPDATE admins
    SET status = 'deleted'
    WHERE id = ?
");
$stmt->execute([$adminId]);

$_SESSION['message'] = "Admin deleted successfully.";
header("Location: admin.php");
exit();
