<?php
function log_admin_activity(PDO $pdo, string $action, string $target)
{
    if (empty($_SESSION['admin']['email'])) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_logs
        (admin_email, action, target, ip_address)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['admin']['email'],
        $action,
        $target,
        $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ]);
}
