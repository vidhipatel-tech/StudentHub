<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/mysqli.php';

// Remove the remember-me token from the database and the browser.
if (!empty($_COOKIE['studenthub_remember'])) {
    $parts = explode(':', (string)$_COOKIE['studenthub_remember'], 2);
    if (count($parts) === 2 && ctype_digit($parts[0])) {
        $id = (int)$parts[0];
        $stmt = $mysqli->prepare('UPDATE users SET remember_token_hash = NULL, remember_expires = NULL WHERE user_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
}
setcookie('studenthub_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ../home.html');
exit;
