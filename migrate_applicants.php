<?php
require 'db.php';

$file = 'users.json';
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

if (!is_array($users)) {
    die("Invalid users.json file");
}

foreach ($users as $user) {

    // ✅ Skip invalid records
    if (
        !isset($user['email']) ||
        !isset($user['password'])
    ) {
        continue;
    }

    $name  = $user['name'] ?? '';
    $email = trim($user['email']);
    $password = $user['password'];

    // 🔍 Prevent duplicates
    $check = $pdo->prepare("SELECT id FROM applicants WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        continue;
    }

    // ✅ Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO applicants (fullname, email, password)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$name, $email, $password]);
}

echo "Migration completed successfully.";
