<?php
session_start();

// Remove only admin session
unset($_SESSION['admin']);

// Optional: regenerate session ID for security
session_regenerate_id(true);

// Redirect to admin login
header("Location: admin_login.php");
exit();
