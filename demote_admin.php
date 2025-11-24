<?php
session_start();

$admins_file = "admins.json";
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

// Validate current admin
$current = null;
foreach ($admins as $a) {
    if ($a['email'] === ($_SESSION['admin'] ?? '')) {
        $current = $a;
        break;
    }
}

if (!$current || empty($current['is_super'])) {
    $_SESSION['message'] = "Only Super Admin can demote admins.";
    header("Location: admin.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$found = false;

// Count number of super-admins
$super_count = 0;
foreach ($admins as $a) if (!empty($a['is_super'])) $super_count++;

foreach ($admins as &$adm) {
    if (intval($adm['id']) === $id) {

        if (empty($adm['is_super'])) {
            $_SESSION['message'] = "This admin is not a Super Admin.";
            header("Location: admin.php");
            exit();
        }

        if ($adm['email'] === $_SESSION['admin']) {
            $_SESSION['message'] = "You cannot demote yourself.";
            header("Location: admin.php");
            exit();
        }

        if ($super_count <= 1) {
            $_SESSION['message'] = "Cannot demote the only Super Admin.";
            header("Location: admin.php");
            exit();
        }

        $adm['is_super'] = false;
        $found = true;
        break;
    }
}

if ($found) {
    file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
    $_SESSION['message'] = "Super Admin demoted successfully.";
} else {
    $_SESSION['message'] = "Admin not found.";
}

header("Location: admin.php");
exit();
