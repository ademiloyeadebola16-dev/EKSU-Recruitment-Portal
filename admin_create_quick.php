<?php
session_start();
if (!isset($_SESSION['admin_email']) || empty($_SESSION['is_super'])) {
    header("Location: admin_login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit();
}

function load(){ $f='admins.json'; return file_exists($f) ? json_decode(file_get_contents($f), true) : []; }
function save($a){ file_put_contents('admins.json', json_encode(array_values($a), JSON_PRETTY_PRINT)); }

$admins = load();
$max=0; foreach($admins as $a) if($a['id']>$max) $max=$a['id'];

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd = $_POST['password'] ?? '';

if(!$name || !$email || !$pwd) {
    $_SESSION['message'] = "All fields required.";
    header("Location: admin.php");
    exit();
}

// ensure unique email
foreach($admins as $a) {
    if (strcasecmp($a['email'],$email)===0) {
        $_SESSION['message'] = "Admin with that email exists.";
        header("Location: admin.php");
        exit();
    }
}

$new = [
    'id'=> $max+1,
    'email'=>$email,
    'name'=>$name,
    'password_hash'=>password_hash($pwd,PASSWORD_DEFAULT),
    'approved'=>true,
    'is_super'=>false,
    'date'=>date('Y-m-d H:i:s')
];
$admins[]=$new;
save($admins);
$_SESSION['message']="Admin created and approved.";
header("Location: admin.php");
exit();
