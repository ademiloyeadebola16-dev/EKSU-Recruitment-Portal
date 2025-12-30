<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin']['role'] !== 'super') {
    die("Access denied");
}

$admins_file = 'admins.json';
$admins = file_exists($admins_file)
    ? json_decode(file_get_contents($admins_file), true)
    : [];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    // Check if admin already exists
    foreach ($admins as $a) {
        if ($a['email'] === $email) {
            $message = "Admin already exists";
            break;
        }
    }

    if (!$message) {
        $admins[] = [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin'
        ];

        file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
        $message = "Admin created successfully";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin</title>
</head>
<body>
<h2>Create New Admin</h2>

<form method="post">
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Create Admin</button>
</form>

<p><?= htmlspecialchars($message) ?></p>
</body>
</html>
