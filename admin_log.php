<?php
function log_admin_activity(PDO $pdo, $action, $target = null) {
    if (!isset($_SESSION['admin'])) return;

    $admin = $_SESSION['admin'];

    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_logs
        (admin_id, admin_email, action, target, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $admin['id'],
        $admin['email'],
        $action,
        $target,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}
