<?php
$token = $_GET['token'] ?? '';
$resetsFile = 'password_resets.json';
$usersFile = 'users.json';
$resets = file_exists($resetsFile) ? json_decode(file_get_contents($resetsFile), true) : [];

if (!isset($resets[$token]) || $resets[$token]['expires'] < time()) {
    die("Invalid or expired reset token.");
}

$email = $resets[$token]['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $users = json_decode(file_get_contents($usersFile), true);
    foreach ($users as &$user) {
        if ($user['email'] === $email) {
            $user['password'] = $newPassword;
            break;
        }
    }
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));

    // Remove used token
    unset($resets[$token]);
    file_put_contents($resetsFile, json_encode($resets, JSON_PRETTY_PRINT));

    header("Location: applicant_login.php?reset=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<style>
body {font-family: Arial; background:#eef1f5;}
.container {max-width:400px;margin:80px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
input {width:100%;padding:10px;margin:10px 0;border:1px solid #ccc;border-radius:5px;}
button {width:100%;padding:10px;background:#800000;color:white;border:none;border-radius:5px;cursor:pointer;}
button:hover {background:#800000;}
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
      align-items: center;  /* vertically center items */
      gap: 15px;            /* space between logo and text */
    }
    .nav-container img {
      width: 70px;
      height: 70px;
      border-radius: 5px; /* optional: rounded edges */
    }

    nav h1 {
      font-size: 22px;
      margin: 0;
    }
    .nav-text h5 {
      margin: 0;
      font-size: 14px;
      font-weight: normal;
      color: #ddd; /* lighter gray subtitle */
    }
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
    </nav>
<div class="container">
  <h2>Reset Your Password</h2>
  <form method="POST">
    <input type="password" name="password" placeholder="Enter new password" required>
    <button type="submit">Reset Password</button>
  </form>
</div>
</body>
</html>
