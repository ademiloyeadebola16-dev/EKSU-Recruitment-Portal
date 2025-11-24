<?php
// One-time script to create Super Admin
$admins_file = 'admins.json';

// Configuration: change these
$email = 'princessadebola4mat@gmail.com';
$name = 'Primary Admin';
$password_plain = 'Adebola20';

// Load existing admins
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

// Check if email already exists
foreach ($admins as $a) {
    if ($a['email'] === $email) {
        die("Admin with this email already exists.");
    }
}

// Generate password hash
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

// Create new super-admin entry
$new_admin = [
    'id' => (count($admins) > 0) ? max(array_column($admins, 'id')) + 1 : 1,
    'email' => $email,
    'name' => $name,
    'password_hash' => $password_hash,
    'approved' => true,
    'is_super' => true,
    'date' => date('Y-m-d H:i:s')
];

// Append to admins array
$admins[] = $new_admin;

// Save back to file
file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));

echo "Super Admin account created successfully!\n";
echo "Email: $email\n";
echo "Password: $password_plain\n";
echo "⚠️ DELETE THIS SCRIPT IMMEDIATELY FOR SECURITY.\n";
