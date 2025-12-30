<?php
session_start();

$admins_file = 'admins.json';
$admins = file_exists($admins_file)
    ? json_decode(file_get_contents($admins_file), true)
    : [];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    foreach ($admins as $admin) {
        if (strtolower($admin['email']) === $email) {

            if (password_verify($password, $admin['password'])) {

                $_SESSION['admin'] = [
                    'id'    => $admin['id'],
                    'email' => $admin['email'],
                    'role'  => $admin['role']
                ];

                header("Location: admin.php");
                exit();
            } else {
                $error = "Invalid email or password.";
                break;
            }
        }
    }

    if (!$error) {
        $error = "Invalid email or password.";
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<style>
body {font-family: Arial; background:#eef1f5;}
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
    /* Password container fix */
.password-wrapper {
    position: relative;
}
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 15px;
    color: #555;
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
     <div>
       <a href="index.php" style="background:white; color:#004080; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold;">Home</a>
      </div>
    </nav>

<div class="container">
  <h2>Admin Login</h2>

  <?php if (!empty($_SESSION['message'])): ?>
    <div class="notice"><?=htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="notice"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>

  <form method="post">
    <input name="email" type="email" placeholder="Email" required>

    <div class="password-wrapper">
        <input name="password" id="password" type="password" placeholder="Password" required>
        <span class="password-toggle" onclick="togglePassword('password', this)">👁️</span>
    </div>

    <button type="submit">Login</button>
  </form>

  <p style="text-align:center; margin-top:10px; color:#777;">
  Admin access is granted by the Super Administrator.
</p>

</div>

<script>
function togglePassword(fieldId, iconElement) {
    let field = document.getElementById(fieldId);

    if (field.type === "password") {
        field.type = "text";
        iconElement.textContent = "🙈"; // hide
    } else {
        field.type = "password";
        iconElement.textContent = "👁️"; // show
    }
}
</script>

</body>
</html>
