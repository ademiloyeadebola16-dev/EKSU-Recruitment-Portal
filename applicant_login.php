<?php
// Show reset success message
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    echo "<p style='color:green; text-align:center;'><strong>Password reset successful. You may now log in.</strong></p>";
}

session_start();
require 'db.php'; // ✅ USE YOUR EXISTING DB CONNECTION

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 🔍 Fetch applicant from DB
    $stmt = $pdo->prepare("SELECT * FROM applicants WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        // ✅ SAME session logic as JSON version
        $_SESSION['applicant'] = $user;
        $_SESSION['applicant_email'] = $user['email'];

        header("Location: applicant_dashboard.php");
        exit();
    }

    // Login failed
    $error = "Invalid email or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicant Login</title>

<style>
body {font-family: Arial; background:#eef1f5;}
.container {
    max-width:400px;
    margin:80px auto;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
input {
    width:100%;
    padding:10px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:5px;
}
button {
    width:100%;
    padding:10px;
    background:#800000;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
button:hover {background:#800000;}
a {color:#800000;text-decoration:none;}

nav {
    background:#800000; 
    color:#fff;
    padding:15px 10%;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.nav-container {
    display:flex;
    align-items:center;
    gap:15px;
}
.nav-container img {
    width:70px;
    height:70px;
    border-radius:5px;
}
nav h1 {
    font-size:22px;
    margin:0;
}
.nav-text h5 {
    margin:0;
    font-size:14px;
    font-weight:normal;
    color:#ddd;
}
</style>
</head>

<body>

<nav>
    <div class="nav-container">
        <img src="logo.jfif" alt="Site Logo">
        <div class="nav-text">
            <h1>Ekiti State University, Ado-Ekiti</h1>
            <h5>Recruitment Portal</h5>
        </div>
    </div>
    <div>
        <a href="index.php"
           style="background:white; color:#004080; padding:8px 15px; border-radius:5px; font-weight:bold;">
           Home
        </a>
    </div>
</nav>

<div class="container">
    <h2>Applicant Login</h2>

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">Signup successful! Please log in.</p>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <p><a href="forgot_password.php">Forgot your password?</a></p>
    <p>Don’t have an account? <a href="applicant_signup.php">Sign up here</a></p>
</div>

</body>
</html>
