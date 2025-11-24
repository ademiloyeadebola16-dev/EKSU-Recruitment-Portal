<?php
session_start();

$admins_file = "admins.json";
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

// Verify current admin is super-admin
$current = null;
foreach ($admins as $a) {
    if ($a['email'] === ($_SESSION['admin'] ?? '')) {
        $current = $a;
        break;
    }
}

if (!$current || empty($current['is_super'])) {
    $_SESSION['message'] = "Only Super Admin can delete admins.";
    header("Location: admin.php");
    exit();
}

$id = intval($_POST['id'] ?? 0);
$new_list = [];
$found = false;

foreach ($admins as $adm) {
    if (intval($adm['id']) === $id) {

        if (!empty($adm['is_super'])) {
            $_SESSION['message'] = "You cannot delete a Super Admin.";
            header("Location: admin.php");
            exit();
        }

        if ($adm['email'] === $_SESSION['admin']) {
            $_SESSION['message'] = "You cannot delete yourself.";
            header("Location: admin.php");
            exit();
        }

        $found = true;
        continue; // remove
    }

    $new_list[] = $adm;
}

file_put_contents($admins_file, json_encode($new_list, JSON_PRETTY_PRINT));

$_SESSION['message'] = $found
    ? "Admin deleted successfully."
    : "Admin not found.";

header("Location: admin.php");
exit();
