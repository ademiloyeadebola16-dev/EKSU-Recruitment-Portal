<?php
session_start();

$file = 'users.json';
$users = json_decode(file_get_contents($file), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $found = false;

    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['admin'] = $username;
            $found = true;
            break;
        }
    }

    if ($found) {
        header("Location: admin.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid username or password!";
        header("Location: index.php");
        exit();
    }
}
?>