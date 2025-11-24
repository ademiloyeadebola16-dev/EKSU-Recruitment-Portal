<?php
session_start();

$admins_file = "admins.json";
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

// Validate session user
$current = null;
foreach ($admins as $a) {
    if ($a['email'] === ($_SESSION['admin'] ?? '')) {
        $current = $a;
        break;
    }
}

// Only super admin can promote others
if (!$current || empty($current['is_super'])) {
    $_SESSION['message'] = "Only Super Admin can promote admins.";
    header("Location: admin.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$found = false;

foreach ($admins as &$adm) {
    if (intval($adm['id']) === $id) {

        if (!empty($adm['is_super'])) {
            $_SESSION['message'] = "This admin is already a Super Admin.";
            header("Location: admin.php");
            exit();
        }

        $adm['is_super'] = true;
        $adm['approved'] = true;
        $found = true;
        break;
    }
}

if ($found) {
    file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
    $_SESSION['message'] = "Admin promoted to Super Admin successfully.";
} else {
    $_SESSION['message'] = "Admin not found.";
}

header("Location: admin.php");
exit();
