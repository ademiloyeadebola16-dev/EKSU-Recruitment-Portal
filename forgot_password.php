<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $usersFile = 'users.json';
    $resetsFile = 'password_resets.json';

    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
    $resets = file_exists($resetsFile) ? json_decode(file_get_contents($resetsFile), true) : [];

    $found = false;
    foreach ($resets as $key => $entry) {
    if ($entry['expires'] < time()) {
        unset($resets[$key]);
    }
}
file_put_contents($resetsFile, json_encode($resets, JSON_PRETTY_PRINT));

    foreach ($users as $user) {
        if ($user['email'] === $email) {
            $found = true;
            $token = bin2hex(random_bytes(16));
            $resets[$token] = [
                'email' => $email,
                'expires' => time() + 900 // 15 minutes
            ];
            file_put_contents($resetsFile, json_encode($resets, JSON_PRETTY_PRINT));
            $resetLink = "reset_password.php?token=$token";
        }
    }

    if ($found) {
        $success = "Password reset link generated! Copy this link: <br><a href='$resetLink'>$resetLink</a>";
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<style>
body {font-family: Arial; background:#f0f2f5;}
.container {max-width:400px;margin:80px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
input {width:100%;padding:10px;margin:10px 0;border:1px solid #ccc;border-radius:5px;}
button {width:100%;padding:10px;background:#004080;color:white;border:none;border-radius:5px;cursor:pointer;}
button:hover {background:#00264d;}
a {color:#004080;text-decoration:none;}
</style>
</head>
<body>

<div class="container">
  <h2>Forgot Password</h2>
  <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
  <form method="POST">
    <input type="email" name="email" placeholder="Enter your registered email" required>
    <button type="submit">Send Reset Link</button>
  </form>
  <p><a href="applicant_login.php">Back to Login</a></p>
</div>
</body>
</html>
