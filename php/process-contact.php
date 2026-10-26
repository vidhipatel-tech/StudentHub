<?php
declare(strict_types=1);

// Contact form: validates the input on the server and appends it to storage/contact_messages.csv.
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sh_result_page('Invalid Request', 'The contact form must be submitted using POST.', false, 405, '../contact.html', 'Back to Contact');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if (!preg_match('/^\p{L}[\p{L} .\'-]{1,49}$/u', $name)) $errors[] = 'Enter a valid full name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
if (mb_strlen($message) < 5 || mb_strlen($message) > 1000) $errors[] = 'Message must contain 5 to 1000 characters.';
if ($errors) sh_result_page('Message Not Sent', implode(' ', $errors), false, 422, '../contact.html', 'Back to Contact');

$file = __DIR__ . '/../storage/contact_messages.csv';
$isNew = !file_exists($file) || filesize($file) === 0;
$handle = @fopen($file, 'ab');
if ($handle === false) {
    sh_result_page('Message Not Sent', 'The message could not be stored. Check the storage folder permissions.', false, 500, '../contact.html', 'Back to Contact');
}
flock($handle, LOCK_EX);
if ($isNew) fputcsv($handle, ['submitted_at', 'name', 'email', 'message']);
fputcsv($handle, [date('Y-m-d H:i:s'), $name, strtolower($email), $message]);
flock($handle, LOCK_UN);
fclose($handle);

sh_result_page('Message Sent', 'Thank you. Your message has been sent to StudentHub support.', true, 200, '../home.html');
