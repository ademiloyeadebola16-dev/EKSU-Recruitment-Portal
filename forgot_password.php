<?php
session_start();
require "send_reset_link.php";

$usersFile = "users.json";
$resetsFile = "password_resets.json";

if (!file_exists($resetsFile)) {
    file_put_contents($resetsFile, json_encode([]));
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Load users
    $users = json_decode(file_get_contents($usersFile), true);

    // Check if email exists
    $found = false;
    foreach ($users as $u) {
        if (strtolower($u['email']) === strtolower($email)) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        $message = "This email does not exist in our system.";
    } else {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = time() + (60 * 30); // 30 mins

        // Save token
        $resetData = json_decode(file_get_contents($resetsFile), true);
        $resetData[$email] = [
            "token" => $token,
            "expires" => $expires
        ];
        file_put_contents($resetsFile, json_encode($resetData, JSON_PRETTY_PRINT));

        // Reset link
        $resetLink = "http://localhost/My%20code/My%20PHP%20codes/reset_password.php?token=".$token;


        // Send email
        if (sendResetLink($email, $resetLink)) {
            $message = "A password reset link has been sent to your email.";
        } else {
            $message = "Error sending email. Contact support.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<style>
body {font-family: Arial; background:#eef1f5;}
.container {max-width:400px;margin:80px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
input {width:100%;padding:10px;margin:10px 0;border:1px solid #ccc;border-radius:5px;}
button {width:100%;padding:10px;background:#800000;color:white;border:none;border-radius:5px;cursor:pointer;}
button:hover {background:#5a0000;}
a {color:#800000;text-decoration:none;}
nav {
      background: #800000; 
      color: rgb(255, 255, 255);
      padding: 15px 10%;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .nav-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .nav-container img {
      width: 70px;
      height: 70px;
      border-radius: 5px;
    }
    nav h1 {font-size: 22px; margin: 0;}
    .nav-text h5 {margin: 0; font-size: 14px; color: #ddd;}
</style>
</head>
<body>
<nav>
   <div class="nav-container">
      <img src="logo.jfif" alt="Site Logo">
      <div class="nav-text">
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Job Recruitment Portal</h5>
      </div>
    </div>
     <div>
       <a href="index.php" style="background:white; color:#004080; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold;">Home</a>
      </div>
</nav>

<?php if ($message): ?>
<p style="color:#800000;"><strong><?= $message ?></strong></p>
<?php endif; ?>

<form method="post">
    <label>Email Address</label><br>
    <input type="email" name="email" required><br><br>
    <button type="submit">Send Reset Link</button>
</form>

</body>
</html>
