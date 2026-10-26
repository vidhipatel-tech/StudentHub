<?php
declare(strict_types=1);

/*
 * Session handling, session timeout, remember-me restore, role checks and
 * CSRF helpers. Include this at the top of every protected page.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
sh_session();
require_once __DIR__ . '/mysqli.php';

/** URL of a project file, correct from both the root and the php/ folder. */
function appUrl(string $path = ''): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname($script);
    $base = basename($dir) === 'php' ? dirname($dir) : $dir;
    if ($base === '/' || $base === '.' || $base === '\\') {
        $base = '';
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function dashboardFor(string $role): string
{
    return $role === 'admin' ? 'admin-dashboard.php' : 'student-dashboard.php';
}

// Expire sessions that have been idle for too long.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > SESSION_TIMEOUT) {
        unset($_SESSION['user_id'], $_SESSION['name'], $_SESSION['username'], $_SESSION['email'], $_SESSION['role'], $_SESSION['last_activity']);
        $_SESSION['login_message'] = 'Your session expired after 15 minutes of inactivity. Please log in again.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// Restore a session from a valid remember-me cookie.
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['studenthub_remember'])) {
    $parts = explode(':', (string)$_COOKIE['studenthub_remember'], 2);
    if (count($parts) === 2 && ctype_digit($parts[0]) && strlen($parts[1]) >= 32) {
        $userId = (int)$parts[0];
        $tokenHash = hash('sha256', $parts[1]);
        $stmt = $mysqli->prepare('SELECT user_id, name, username, email, role FROM users WHERE user_id = ? AND remember_token_hash = ? AND remember_expires > NOW()');
        $stmt->bind_param('is', $userId, $tokenHash);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
        }
    }
}

/** Sends guests to the login page and blocks accounts that have the wrong role. */
function requireRole(string $role): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . appUrl('login.php'));
        exit;
    }
    $current = (string)($_SESSION['role'] ?? '');
    if ($current !== $role) {
        sh_result_page(
            'Access denied',
            'This page is not available for your account role.',
            false,
            403,
            appUrl(dashboardFor($current)),
            'Go to my dashboard'
        );
    }
}

/** One CSRF token per session, shared by admin forms and the Fetch API. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function csrfValid(): bool
{
    return hash_equals(csrfToken(), (string)($_POST['csrf_token'] ?? ''));
}
