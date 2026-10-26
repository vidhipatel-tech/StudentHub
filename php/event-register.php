<?php
declare(strict_types=1);

// Registers the signed-in student for an event. Called with fetch() from events.html.
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, false, 'Use POST to register for an event.');
// A JSON body plus a custom header cannot be sent by a cross-site HTML form.
if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0 || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
    reply(400, false, 'Invalid request.');
}
if (!isset($_SESSION['user_id'])) reply(401, false, 'Please log in first.');
if (($_SESSION['role'] ?? '') !== 'student') reply(403, false, 'Only student accounts can register for events.');

$input = json_decode((string)file_get_contents('php://input'), true);
$eventId = is_array($input) ? filter_var($input['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
if (!$eventId) reply(422, false, 'A valid event is required.');

$email = (string)($_SESSION['email'] ?? '');
$stmt = $mysqli->prepare('SELECT student_id FROM students WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) reply(404, false, 'No student record is linked to your account. Please contact the administrator.');

$check = $mysqli->prepare('SELECT title FROM events WHERE event_id = ?');
$check->bind_param('i', $eventId);
$check->execute();
$event = $check->get_result()->fetch_assoc();
if (!$event) reply(404, false, 'This event is not available for registration yet.');

try {
    $insert = $mysqli->prepare('INSERT INTO registrations (student_id, event_id) VALUES (?, ?)');
    $insert->bind_param('si', $student['student_id'], $eventId);
    $insert->execute();
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) reply(409, false, 'You are already registered for this event.');
    reply(500, false, 'The registration could not be saved.');
}

reply(200, true, 'You are registered for ' . $event['title'] . '.');
