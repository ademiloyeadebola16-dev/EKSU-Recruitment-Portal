<?php
session_start();
$admins_file = 'admins.json';
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    foreach ($admins as $admin) {
        if (isset($admin['email']) && strtolower($admin['email']) === $email) {
            if (!($admin['approved'] ?? false)) {
                $error = "Your account is pending admin approval.";
                break;
            }
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = $admin['email'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['is_super'] = !empty($admin['is_super']);
                header("Location: admin.php");
                exit();
            } else {
                $error = "Invalid credentials.";
            }
        }
    }
    if (empty($error)) $error = "Account not found.";
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f7fb; }
.container { max-width:420px; margin:50px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.08); }
input { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:5px; }
button { background:#800000; color:#fff; padding:10px; border:none; border-radius:5px; cursor:pointer; width:100%; }
button:hover { background:#660000; }
.notice { color:#800000; margin:8px 0; }
h2 { color:#800000; text-align:center; }
a { color:#800000; text-decoration:none; }
a:hover { text-decoration:underline; }

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

  <p style="text-align:center; margin-top:10px;">
    <a href="admin_signup.php">Request admin access</a>
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
