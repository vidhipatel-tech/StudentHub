<?php
declare(strict_types=1);

// Admin: confirms the PDO connection and a prepared statement work.
require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/db.php';

$stmt = $pdo->prepare('SELECT 1 AS connection_ok');
$stmt->execute();
$result = $stmt->fetch();

sh_result_page(
    'Database Connected',
    'MySQL connection through PDO is working. Prepared statement test result: ' . (string)$result['connection_ok'] . '.',
    true,
    200,
    '../admin-dashboard.php',
    'Back to Admin Dashboard'
);
