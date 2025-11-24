<?php
session_start();

// Helper to load admins
function load_admins() {
    $file = 'admins.json';
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

// Helper to save admins
function save_admins($admins) {
    file_put_contents('admins.json', json_encode(array_values($admins), JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$name || !$password) {
        $error = "Please fill all fields.";
    } else {

        $admins = load_admins();

        // Ensure email is unique
        foreach ($admins as $admin) {
            if (strcasecmp($admin['email'], $email) === 0) {
                $error = "An admin with this email already exists.";
                break;
            }
        }

        if (empty($error)) {

            // Generate unique ID
            $maxId = 0;
            foreach ($admins as $admin) {
                if (isset($admin['id']) && $admin['id'] > $maxId) {
                    $maxId = $admin['id'];
                }
            }

            // Construct new admin record
            $newAdmin = [
                'id'            => $maxId + 1,
                'name'          => $name,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'approved'      => false, // super admin must approve
                'is_super'      => false,
                'date'          => date('Y-m-d H:i:s')
            ];

            // Save into JSON
            $admins[] = $newAdmin;
            save_admins($admins);

            $_SESSION['message'] = "Signup successful. Your account is awaiting approval by the Super Admin.";
            header("Location: admin_login.php");
            exit();
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Signup</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 420px;
    margin: 70px auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h2 {
    color: #800000;
    text-align: center;
}
input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* Password wrapper */
.password-wrapper {
    position: relative;
}
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 16px;
    color: #555;
}

button {
    width: 100%;
    background: #800000;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}
button:hover {
    background: #5e0000;
}
.notice {
    background: #ffe5e5;
    padding: 10px;
    border-left: 4px solid #800000;
    margin-bottom: 15px;
    border-radius: 5px;
    color: #800000;
}
a {
    color: #800000;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="container">
    <h2>Admin Signup</h2>

    <?php if (!empty($error)): ?>
        <div class="notice"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">

        <input name="name" placeholder="Full Name" required>

        <input name="email" type="email" placeholder="Email Address" required>

        <div class="password-wrapper">
            <input name="password" id="password" type="password" placeholder="Password" required>
            <span class="password-toggle" onclick="togglePassword('password', this)">👁️</span>
        </div>

        <button type="submit">Request Admin Access</button>
    </form>

    <p style="margin-top: 12px; text-align:center;">
        <a href="admin_login.php">Back to Login</a>
    </p>
</div>

<script>
function togglePassword(id, icon) {
    let field = document.getElementById(id);
    if (field.type === "password") {
        field.type = "text";
        icon.textContent = "🙈";
    } else {
        field.type = "password";
        icon.textContent = "👁️";
    }
}
</script>

</body>
</html>
