<?php
session_start();

$usersFile = "users.json";
$resetsFile = "password_resets.json";

$email = $_GET['email'] ?? "";
$token = $_GET['token'] ?? "";

if (!$email || !$token) {
    die("Invalid reset link.");
}

// Load reset tokens
$resetData = json_decode(file_get_contents($resetsFile), true);

if (
    !isset($resetData[$email]) ||
    $resetData[$email]['token'] !== $token ||
    $resetData[$email]['expires'] < time()
) {
    die("Reset link is invalid or has expired.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];

    if ($pass1 !== $pass2) {
        $message = "Passwords do not match.";
    } else {
        // Load users
        $users = json_decode(file_get_contents($usersFile), true);

        // Update password
        foreach ($users as &$u) {
            if (strtolower($u['email']) === strtolower($email)) {
                $u['password'] = password_hash($pass1, PASSWORD_DEFAULT);
                break;
            }
        }

        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));

        // Remove used token
        unset($resetData[$email]);
        file_put_contents($resetsFile, json_encode($resetData, JSON_PRETTY_PRINT));

        // Redirect to login
        header("Location: login.php?reset=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
</head>
<body>

<h2>Reset Your Password</h2>

<?php if ($message): ?>
<p style="color:red;"><strong><?= $message ?></strong></p>
<?php endif; ?>

<form method="post">
    <label>New Password</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirm Password</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit">Reset Password</button>
</form>

</body>
</html>
