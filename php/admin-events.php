<?php
declare(strict_types=1);

// Admin: event management (create, read, update, delete, poster upload with validation).
require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/audit.php';

$notice = '';
$noticeType = 'success';

function removePoster(string $path): void
{
    $prefix = 'uploads/event-posters/';
    if (str_starts_with($path, $prefix)) {
        $full = __DIR__ . '/' . $path;
        if (is_file($full)) unlink($full);
    }
}

/** Validates and stores an uploaded poster. Returns its path, or '' when none was sent. */
function savePoster(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('The poster upload failed.');
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) throw new RuntimeException('Poster must be 2 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($types[$mime])) throw new RuntimeException('Only JPG, PNG or WebP posters are allowed.');
    $relative = 'uploads/event-posters/' . bin2hex(random_bytes(12)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $relative)) {
        throw new RuntimeException('Poster could not be saved. Check the uploads folder permissions.');
    }
    return $relative;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!csrfValid()) {
        $notice = 'The form expired. Refresh the page and try again.';
        $noticeType = 'error';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['event_id'] ?? 0);
        $find = $mysqli->prepare('SELECT image FROM events WHERE event_id = ?');
        $find->bind_param('i', $id);
        $find->execute();
        $old = $find->get_result()->fetch_assoc();
        $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows) {
            if ($old) removePoster((string)$old['image']);
            writeAuditLog($mysqli, 'delete', 'event', (string)$id, 'Deleted event');
            $notice = 'Event deleted successfully.';
        } else {
            $notice = 'Event record was not found.';
            $noticeType = 'error';
        }
    } elseif ($action === 'save') {
        $id = (int)($_POST['event_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $date = trim((string)($_POST['event_date'] ?? ''));
        $time = trim((string)($_POST['event_time'] ?? ''));
        $venue = trim((string)($_POST['venue'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $oldImage = trim((string)($_POST['old_image'] ?? ''));
        try {
            if ($title === '' || mb_strlen($title) > 150 || $category === '' || mb_strlen($category) > 50
                || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $time === '' || mb_strlen($time) > 30
                || $venue === '' || mb_strlen($venue) > 150 || $description === '') {
                throw new RuntimeException('Complete all event fields with valid values.');
            }
            $newImage = savePoster($_FILES['poster'] ?? []);
            $image = $newImage !== '' ? $newImage : $oldImage;
            if ($id > 0) {
                $stmt = $mysqli->prepare('UPDATE events SET title=?, category=?, event_date=?, event_time=?, venue=?, description=?, image=? WHERE event_id=?');
                $stmt->bind_param('sssssssi', $title, $category, $date, $time, $venue, $description, $image, $id);
                $stmt->execute();
                if ($newImage !== '') removePoster($oldImage);
                writeAuditLog($mysqli, 'update', 'event', (string)$id, 'Updated event: ' . $title);
                $notice = 'Event updated successfully.';
            } else {
                $stmt = $mysqli->prepare('INSERT INTO events (title, category, event_date, event_time, venue, description, image) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('sssssss', $title, $category, $date, $time, $venue, $description, $image);
                $stmt->execute();
                writeAuditLog($mysqli, 'create', 'event', (string)$mysqli->insert_id, 'Added event: ' . $title);
                $notice = 'Event added successfully.';
            }
        } catch (Throwable $e) {
            $notice = $e instanceof RuntimeException ? $e->getMessage() : 'The event could not be saved.';
            $noticeType = 'error';
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare('SELECT * FROM events WHERE event_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
}

$q = trim((string)($_GET['q'] ?? ''));
$search = '%' . $q . '%';
$stmt = $mysqli->prepare('SELECT * FROM events WHERE title LIKE ? OR category LIKE ? OR venue LIKE ? ORDER BY event_date DESC, event_id DESC');
$stmt->bind_param('sss', $search, $search, $search);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sh_head(['title' => 'Event Management']);
sh_dashboard_open('admin', 'events');
?>
        <div class="dashboard-title">
          <div>
            <div class="kicker">Admin</div>
            <h1>Events</h1>
          </div>
          <span class="muted"><?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?> shown</span>
        </div>

        <?php if ($notice): ?><div class="message-box <?= h($noticeType) ?>" role="status"><?= h($notice) ?></div><?php endif; ?>

        <form class="form-card admin-form" method="post" enctype="multipart/form-data">
          <h2><?= $edit ? 'Update event' : 'Add event' ?></h2>
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="event_id" value="<?= h((string)($edit['event_id'] ?? 0)) ?>">
          <input type="hidden" name="old_image" value="<?= h($edit['image'] ?? '') ?>">
          <div class="form-grid">
            <div class="form-group"><label for="title">Event title</label><input id="title" name="title" maxlength="150" value="<?= h($edit['title'] ?? '') ?>" required></div>
            <div class="form-group"><label for="category">Category</label><input id="category" name="category" maxlength="50" value="<?= h($edit['category'] ?? '') ?>" placeholder="e.g. Technical" required></div>
            <div class="form-group"><label for="event_date">Event date</label><input id="event_date" name="event_date" type="date" value="<?= h($edit['event_date'] ?? '') ?>" required></div>
            <div class="form-group"><label for="event_time">Event time</label><input id="event_time" name="event_time" maxlength="30" value="<?= h($edit['event_time'] ?? '') ?>" placeholder="e.g. 10:00 AM" required></div>
            <div class="form-group"><label for="venue">Venue</label><input id="venue" name="venue" maxlength="150" value="<?= h($edit['venue'] ?? '') ?>" required></div>
            <div class="form-group"><label for="poster">Poster (JPG, PNG or WebP, max 2 MB)</label><input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp"></div>
          </div>
          <div class="form-group"><label for="description">Description</label><textarea id="description" name="description" rows="4" required><?= h($edit['description'] ?? '') ?></textarea></div>
          <?php if (!empty($edit['image'])): ?><p class="muted">Current poster: <?= h($edit['image']) ?></p><?php endif; ?>
          <button class="btn" type="submit">Save Event</button>
          <?php if ($edit): ?><a class="btn btn-outline" href="admin-events.php">Cancel edit</a><?php endif; ?>
        </form>

        <form class="toolbar single" method="get" style="margin-top:1.5rem">
          <label class="search-box"><span class="sr-only">Search events</span><input name="q" type="search" value="<?= h($q) ?>" placeholder="Search title, category or venue"></label>
        </form>

        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Date</th><th>Venue</th><th>Poster</th><th>Actions</th></tr></thead>
            <tbody>
<?php foreach ($events as $row): ?>
              <tr>
                <td><?= h((string)$row['event_id']) ?></td><td><?= h($row['title']) ?></td><td><?= h($row['category']) ?></td><td><?= h($row['event_date']) ?></td><td><?= h($row['venue']) ?></td>
                <td><?php if (!empty($row['image'])): ?><a href="<?= h(str_starts_with($row['image'], 'uploads/') ? $row['image'] : '../' . $row['image']) ?>" target="_blank" rel="noopener">View poster</a><?php else: ?>—<?php endif; ?></td>
                <td>
                  <div class="action-links">
                    <a class="btn btn-small btn-outline" href="?edit=<?= (int)$row['event_id'] ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this event? Related registrations will also be deleted.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="event_id" value="<?= (int)$row['event_id'] ?>">
                      <button class="btn btn-small" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
<?php if (!$events): ?>
              <tr><td colspan="7">No events found.</td></tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>
<?php
sh_dashboard_close();
sh_foot();
