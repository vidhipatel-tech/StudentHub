<?php
declare(strict_types=1);

/*
 * Tells the static pages who is signed in, so their menu can show
 * "Logout" and only the dashboard link that matches the role.
 * It never touches the database and never creates a session for visitors.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$guest = ['loggedIn' => false, 'role' => null];

if (empty($_COOKIE[session_name()])) {
    echo json_encode($guest);
    exit;
}

session_start();
$active = isset($_SESSION['user_id'])
    && (time() - (int)($_SESSION['last_activity'] ?? 0)) <= SESSION_TIMEOUT;

echo json_encode($active ? ['loggedIn' => true, 'role' => (string)($_SESSION['role'] ?? 'student')] : $guest);
