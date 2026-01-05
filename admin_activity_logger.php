<?php
/**
 * Log admin activities
 *
 * @param PDO $pdo
 * @param string $action
 * @param string|null $target
 */
function log_admin_activity(PDO $pdo, string $action, ?string $target = null): void
{
    if (!isset($_SESSION['admin'])) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_logs 
        (admin_id, admin_email, action, target, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['admin']['id'],
        $_SESSION['admin']['email'],
        $action,
        $target,
        $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ]);
}
