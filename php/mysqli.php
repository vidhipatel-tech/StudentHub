<?php
declare(strict_types=1);

// MySQLi connection used by the whole application.
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    if (basename(dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))) === 'api') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'The database is not available. Start MySQL and import database/studenthub.sql.']);
    } else {
        require_once __DIR__ . '/layout.php';
        sh_result_page(
            'Database unavailable',
            'StudentHub could not connect to MySQL. Start MySQL in XAMPP, create the "studenthub" database and import database/studenthub.sql (see the README).',
            false,
            503
        );
    }
    exit;
}
