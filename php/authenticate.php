<?php
declare(strict_types=1);

// Handles the login form (login.php posts here): verifies the password, starts the session, redirects by role.
session_start();
require_once __DIR__ . '/mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['login_message'] = 'Enter a valid email address and password.';
    header('Location: ../login.php');
    exit;
}

$stmt = $mysqli->prepare('SELECT user_id, name, username, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_message'] = 'Login failed. Check your email and password.';
    header('Location: ../login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['last_activity'] = time();

$update = $mysqli->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?');
$update->bind_param('i', $user['user_id']);
$update->execute();

// Optional remember-me: only a hash of a random token is stored in the database.
if (!empty($_POST['remember'])) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
    $remember = $mysqli->prepare('UPDATE users SET remember_token_hash = ?, remember_expires = ? WHERE user_id = ?');
    $remember->bind_param('ssi', $tokenHash, $expiry, $user['user_id']);
    $remember->execute();
    setcookie('studenthub_remember', $user['user_id'] . ':' . $token, [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

header('Location: ' . ($user['role'] === 'admin' ? '../admin-dashboard.php' : '../student-dashboard.php'));
exit;
