<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $file = 'users.json';
    $users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    // Check if email already exists
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            $error = "Email already registered!";
        }
    }

    if (!isset($error)) {
        $users[] = ['name' => $name, 'email' => $email, 'password' => $password];
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
        header("Location: login.php?success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicant Signup</title>
<style>
body {font-family: Arial; background:#f0f2f5;}
.container {max-width:400px;margin:80px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
input {width:100%;padding:10px;margin:10px 0;border:1px solid #ccc;border-radius:5px;}
button {width:100%;padding:10px;background:#800000;color:white;border:none;border-radius:5px;cursor:pointer;}
button:hover {background:#800000;}
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
  <h2>Create Account</h2>
  <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <form method="POST">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Sign Up</button>
  </form>
 <p>Already have an account? <a href="applicant_login.php">Login here</a></p>
</div>
</body>
</html>
