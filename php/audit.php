<?php
declare(strict_types=1);

/** Records an important operation in the audit_logs table. */
function writeAuditLog(mysqli $mysqli, string $action, string $entityType, ?string $entityId = null, ?string $details = null): void
{
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $stmt = $mysqli->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssss', $userId, $action, $entityType, $entityId, $details, $ip);
    $stmt->execute();
}
