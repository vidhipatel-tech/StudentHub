<?php
declare(strict_types=1);

// Feedback form: validates the input on the server and stores it in storage/feedback.json.
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sh_result_page('Invalid Request', 'The feedback form must be submitted using POST.', false, 405, '../feedback.html', 'Back to Feedback');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$type = trim((string)($_POST['type'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$types = ['Suggestion', 'Portal Experience', 'Event Experience', 'Issue Report'];
$errors = [];
if (!preg_match('/^\p{L}[\p{L} .\'-]{1,49}$/u', $name)) $errors[] = 'Enter a valid full name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
if (!in_array($type, $types, true)) $errors[] = 'Select a valid feedback type.';
if (mb_strlen($message) < 5 || mb_strlen($message) > 1000) $errors[] = 'Feedback must contain 5 to 1000 characters.';
if ($errors) sh_result_page('Feedback Not Submitted', implode(' ', $errors), false, 422, '../feedback.html', 'Back to Feedback');

$file = __DIR__ . '/../storage/feedback.json';
$handle = @fopen($file, 'c+');
if ($handle === false) {
    sh_result_page('Feedback Not Submitted', 'The feedback could not be stored. Check the storage folder permissions.', false, 500, '../feedback.html', 'Back to Feedback');
}
flock($handle, LOCK_EX);
$records = json_decode((string)stream_get_contents($handle), true);
if (!is_array($records)) $records = [];
$records[] = [
    'feedback_id' => 'FDB' . str_pad((string)(count($records) + 1), 3, '0', STR_PAD_LEFT),
    'name' => $name,
    'email' => strtolower($email),
    'type' => $type,
    'message' => $message,
    'submitted_at' => date('Y-m-d H:i:s'),
];
ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
flock($handle, LOCK_UN);
fclose($handle);

sh_result_page('Feedback Submitted', 'Thank you. Your feedback has been recorded.', true, 200, '../home.html');
