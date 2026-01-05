<?php
require 'db.php';

$admins = json_decode(file_get_contents('admins.json'), true);

$stmt = $pdo->prepare(
    "INSERT INTO admins (full_name, email, password, role, status)
     VALUES (?,?,?,?,?)"
);

foreach ($admins as $admin) {
    $stmt->execute([
        $admin['full_name'],
        $admin['email'],
        $admin['password'],
        $admin['role'] ?? 'admin',
        $admin['status'] ?? 'active'
    ]);
}

echo "Admins migrated";
