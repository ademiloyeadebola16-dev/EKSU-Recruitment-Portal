<?php
session_start();
$file = 'users.json';
$users = json_decode(file_get_contents($file), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $found = false;

    foreach ($users as &$user) {
        if ($user['username'] === $username) {
            $found = true;
            // Generate 6-digit reset code
            $user['reset_code'] = rand(100000, 999999);
            file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));

            $_SESSION['reset_user'] = $username;
            $_SESSION['reset_code'] = $user['reset_code'];
            header("Location: reset.php?showcode=1");
            exit();
        }
    }

    if (!$found) {
        $_SESSION['error'] = "Username not found!";
        header("Location: forgot.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <style>
    body {font-family: Arial; background:#f8f9fa;}
    .box {max-width:400px;margin:80px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.1);}
    input {width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:5px;}
    button {background:#800000;color:white;padding:10px;width:100%;border:none;border-radius:5px;}
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
  <div class="box">
    <h3 style="text-align:center;">Forgot Password</h3>

    <?php if(isset($_SESSION['error'])): ?>
      <p style="color:red;text-align:center;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form action="forgot.php" method="POST">
      <input type="text" name="username" placeholder="Enter your username" required>
      <button type="submit">Generate Reset Code</button>
    </form>

    <p style="text-align:center;"><a href="index.php">Back to Login</a></p>
  </div>
</body>
</html>
