<?php
session_start();

$admins_file = "admins.json";
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

// Validate session admin
$current = null;
foreach ($admins as $a) {
    if ($a['email'] === ($_SESSION['admin'] ?? '')) {
        $current = $a;
        break;
    }
}

// Only super-admin can approve
if (!$current || empty($current['is_super'])) {
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
$found = false;

foreach ($admins as &$admin) {
    if (intval($admin['id']) === $id) {

        // Prevent approving another Super Admin (safety)
        if (!empty($admin['is_super'])) {
            $_SESSION['message'] = "Cannot approve another Super Admin.";
            header("Location: admin.php");
            exit();
        }

        // Approve admin
        $admin['approved'] = true;
        $found = true;
        break;
    }
}

if ($found) {
    file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
    $_SESSION['message'] = "Admin account approved successfully.";
} else {
    $_SESSION['message'] = "Admin not found.";
}

header("Location: admin.php");
exit();
