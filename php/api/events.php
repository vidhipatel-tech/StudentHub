<?php
declare(strict_types=1);

// JSON API for events: GET (list/search), POST (create), PUT (update), DELETE. Admin only.
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../audit.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) respond(401, ['success' => false, 'message' => 'Please log in first.']);
if (($_SESSION['role'] ?? '') !== 'admin') respond(403, ['success' => false, 'message' => 'Admin access is required.']);

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($method !== 'GET' && !hash_equals(csrfToken(), $csrf)) {
    respond(419, ['success' => false, 'message' => 'The request expired. Refresh the page and try again.']);
}

function eventInput(array $input): array
{
    $title = trim((string)($input['title'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $date = trim((string)($input['event_date'] ?? ''));
    $time = trim((string)($input['event_time'] ?? ''));
    $venue = trim((string)($input['venue'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    if ($title === '' || mb_strlen($title) > 150) throw new InvalidArgumentException('Enter an event title of up to 150 characters.');
    if ($category === '' || mb_strlen($category) > 50) throw new InvalidArgumentException('Enter a category of up to 50 characters.');
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) || !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1])) throw new InvalidArgumentException('Choose a valid event date.');
    if ($time === '' || mb_strlen($time) > 30) throw new InvalidArgumentException('Enter an event time of up to 30 characters.');
    if ($venue === '' || mb_strlen($venue) > 150) throw new InvalidArgumentException('Enter a venue of up to 150 characters.');
    if ($description === '') throw new InvalidArgumentException('Enter an event description.');
    return [$title, $category, $date, $time, $venue, $description];
}

try {
    if ($method === 'GET') {
        $q = trim((string)($_GET['q'] ?? ''));
        $like = '%' . $q . '%';
        $stmt = $mysqli->prepare('SELECT event_id, title, category, event_date, event_time, venue, description, image, created_at FROM events WHERE title LIKE ? OR category LIKE ? OR venue LIKE ? ORDER BY event_date DESC, event_id DESC');
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        respond(200, ['success' => true, 'events' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw ?: '{}', true);
    if (!is_array($input)) respond(400, ['success' => false, 'message' => 'Request body must be valid JSON.']);

    if ($method === 'POST') {
        [$title, $category, $date, $time, $venue, $description] = eventInput($input);
        $stmt = $mysqli->prepare('INSERT INTO events (title, category, event_date, event_time, venue, description) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $title, $category, $date, $time, $venue, $description);
        $stmt->execute();
        $id = (string)$mysqli->insert_id;
        writeAuditLog($mysqli, 'create', 'event', $id, 'Created event: ' . $title);
        respond(201, ['success' => true, 'message' => 'Event added successfully.', 'event_id' => (int)$id]);
    }

    if ($method === 'PUT') {
        $id = filter_var($input['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) respond(422, ['success' => false, 'message' => 'A valid event ID is required for update.']);
        [$title, $category, $date, $time, $venue, $description] = eventInput($input);
        $stmt = $mysqli->prepare('UPDATE events SET title=?, category=?, event_date=?, event_time=?, venue=?, description=? WHERE event_id=?');
        $stmt->bind_param('ssssssi', $title, $category, $date, $time, $venue, $description, $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $check = $mysqli->prepare('SELECT event_id FROM events WHERE event_id=?');
            $check->bind_param('i', $id); $check->execute();
            if (!$check->get_result()->fetch_assoc()) respond(404, ['success' => false, 'message' => 'Event not found.']);
        }
        writeAuditLog($mysqli, 'update', 'event', (string)$id, 'Updated event: ' . $title);
        respond(200, ['success' => true, 'message' => 'Event updated successfully.']);
    }

    if ($method === 'DELETE') {
        $id = filter_var($input['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) respond(422, ['success' => false, 'message' => 'A valid event ID is required for deletion.']);
        $find = $mysqli->prepare('SELECT title FROM events WHERE event_id=?');
        $find->bind_param('i', $id); $find->execute(); $existing = $find->get_result()->fetch_assoc();
        if (!$existing) respond(404, ['success' => false, 'message' => 'Event not found.']);
        $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id=?');
        $stmt->bind_param('i', $id); $stmt->execute();
        writeAuditLog($mysqli, 'delete', 'event', (string)$id, 'Deleted event: ' . (string)$existing['title']);
        respond(200, ['success' => true, 'message' => 'Event deleted successfully.']);
    }

    header('Allow: GET, POST, PUT, DELETE');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
} catch (InvalidArgumentException $e) {
    respond(422, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    respond(500, ['success' => false, 'message' => 'The request could not be completed. Check the database connection and audit_logs table.']);
}
