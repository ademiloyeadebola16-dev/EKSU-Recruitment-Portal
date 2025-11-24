<?php
session_start();
$file = 'users.json';
$users = json_decode(file_get_contents($file), true);

if (isset($_GET['showcode']) && isset($_SESSION['reset_code'])) {
    // Show the generated code (for testing/demo)
    echo "<div style='margin:50px auto;text-align:center;font-family:Arial;'>
            <h2>Your Reset Code: <span style='color:#004080'>" . $_SESSION['reset_code'] . "</span></h2>
            <p>Use this code below to reset your password.</p>
            <a href='reset.php' style='color:#004080;text-decoration:none;'>Proceed to Reset</a>
          </div>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['code']);
    $new_password = trim($_POST['password']);
    $username = $_SESSION['reset_user'] ?? '';

    if (!$username) {
        $_SESSION['error'] = "Session expired. Try again.";
        header("Location: forgot.php");
        exit();
    }

    $success = false;
    foreach ($users as &$user) {
        if ($user['username'] === $username && isset($user['reset_code']) && $user['reset_code'] == $code) {
            $user['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            unset($user['reset_code']);
            file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
            $success = true;
            break;
        }
    }

    if ($success) {
        $_SESSION['error'] = "Password reset successful! You can now login.";
        unset($_SESSION['reset_user']);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid reset code!";
        header("Location: reset.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {font-family: Arial; background:#f8f9fa;}
    .box {max-width:400px;margin:80px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.1);}
    input {width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:5px;}
    button {background:#004080;color:white;padding:10px;width:100%;border:none;border-radius:5px;}
    button:hover {background:#00264d;}
  </style>
</head>
<body>
  <div class="box">
    <h3 style="text-align:center;">Reset Your Password</h3>

    <?php if(isset($_SESSION['error'])): ?>
      <p style="color:red;text-align:center;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form action="reset.php" method="POST">
      <input type="text" name="code" placeholder="Enter Reset Code" required>
      <input type="password" name="password" placeholder="Enter New Password" required>
      <button type="submit">Reset Password</button>
    </form>

    <p style="text-align:center;"><a href="index.php">Back to Login</a></p>
  </div>
</body>
</html>
