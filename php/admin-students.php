<?php
declare(strict_types=1);

// Admin: student records (create, read, update, delete, search, filter).
require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/audit.php';

$notice = '';
$noticeType = 'success';
$courses = ['B.Tech IT', 'B.Tech CE', 'B.Tech CSE'];
$years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!csrfValid()) {
        $notice = 'The form expired. Refresh the page and try again.';
        $noticeType = 'error';
    } elseif ($action === 'delete') {
        $id = trim((string)($_POST['student_id'] ?? ''));
        $stmt = $mysqli->prepare('DELETE FROM students WHERE student_id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        if ($stmt->affected_rows) {
            $notice = 'Student deleted successfully.';
            writeAuditLog($mysqli, 'delete', 'student', $id, 'Deleted student record');
        } else {
            $notice = 'Student record was not found.';
            $noticeType = 'error';
        }
    } elseif ($action === 'save') {
        $id = strtoupper(trim((string)($_POST['student_id'] ?? '')));
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $mobile = trim((string)($_POST['mobile'] ?? ''));
        $course = (string)($_POST['course'] ?? '');
        $year = (string)($_POST['year'] ?? '');
        $status = (string)($_POST['status'] ?? 'Active');
        $original = trim((string)($_POST['original_id'] ?? ''));

        if (!preg_match('/^[A-Z0-9]{3,10}$/', $id)
            || !preg_match('/^\p{L}[\p{L} .\'-]{1,99}$/u', $name)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile))
            || !in_array($course, $courses, true)
            || !in_array($year, $years, true)
            || !in_array($status, ['Active', 'Inactive'], true)) {
            $notice = 'Check the student ID, name, email, mobile, course, year and status.';
            $noticeType = 'error';
        } else {
            try {
                if ($original !== '') {
                    $stmt = $mysqli->prepare('UPDATE students SET student_id=?, name=?, email=?, mobile=?, course=?, year=?, status=? WHERE student_id=?');
                    $stmt->bind_param('ssssssss', $id, $name, $email, $mobile, $course, $year, $status, $original);
                } else {
                    $stmt = $mysqli->prepare('INSERT INTO students (student_id, name, email, mobile, course, year, status) VALUES (?,?,?,?,?,?,?)');
                    $stmt->bind_param('sssssss', $id, $name, $email, $mobile, $course, $year, $status);
                }
                $stmt->execute();
                $notice = $original !== '' ? 'Student updated successfully.' : 'Student added successfully.';
                writeAuditLog($mysqli, $original !== '' ? 'update' : 'create', 'student', $id, ($original !== '' ? 'Updated student: ' : 'Added student: ') . $name);
            } catch (mysqli_sql_exception $e) {
                $notice = 'Could not save the student. The student ID or email may already exist.';
                $noticeType = 'error';
            }
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (string)$_GET['edit'];
    $stmt = $mysqli->prepare('SELECT * FROM students WHERE student_id = ?');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
}

$q = trim((string)($_GET['q'] ?? ''));
$courseFilter = (string)($_GET['course'] ?? '');
$search = '%' . $q . '%';
$sql = 'SELECT student_id, name, email, mobile, course, year, status FROM students WHERE (name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
if (in_array($courseFilter, $courses, true)) {
    $stmt = $mysqli->prepare($sql . ' AND course = ? ORDER BY student_id');
    $stmt->bind_param('ssss', $search, $search, $search, $courseFilter);
} else {
    $stmt = $mysqli->prepare($sql . ' ORDER BY student_id');
    $stmt->bind_param('sss', $search, $search, $search);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sh_head(['title' => 'Student Management']);
sh_dashboard_open('admin', 'students');
?>
        <div class="dashboard-title">
          <div>
            <div class="kicker">Admin</div>
            <h1>Students</h1>
          </div>
          <span class="muted"><?= count($students) ?> record<?= count($students) === 1 ? '' : 's' ?> shown</span>
        </div>

        <?php if ($notice): ?><div class="message-box <?= h($noticeType) ?>" role="status"><?= h($notice) ?></div><?php endif; ?>

        <form class="form-card admin-form" method="post">
          <h2><?= $edit ? 'Edit student' : 'Add student' ?></h2>
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="original_id" value="<?= h($edit['student_id'] ?? '') ?>">
          <div class="form-grid">
            <div class="form-group"><label for="student_id">Student ID</label><input id="student_id" name="student_id" maxlength="10" value="<?= h($edit['student_id'] ?? '') ?>" required></div>
            <div class="form-group"><label for="name">Full name</label><input id="name" name="name" value="<?= h($edit['name'] ?? '') ?>" required></div>
            <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= h($edit['email'] ?? '') ?>" required></div>
            <div class="form-group"><label for="mobile">Mobile</label><input id="mobile" name="mobile" maxlength="10" inputmode="numeric" value="<?= h($edit['mobile'] ?? '') ?>"></div>
            <div class="form-group"><label for="course">Course</label>
              <select id="course" name="course"><?php foreach ($courses as $c): ?><option<?= ($edit['course'] ?? '') === $c ? ' selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="year">Year</label>
              <select id="year" name="year"><?php foreach ($years as $y): ?><option<?= ($edit['year'] ?? '') === $y ? ' selected' : '' ?>><?= h($y) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="status">Status</label>
              <select id="status" name="status"><option<?= ($edit['status'] ?? 'Active') === 'Active' ? ' selected' : '' ?>>Active</option><option<?= ($edit['status'] ?? '') === 'Inactive' ? ' selected' : '' ?>>Inactive</option></select></div>
          </div>
          <button class="btn" type="submit">Save Student</button>
          <?php if ($edit): ?><a class="btn btn-outline" href="admin-students.php">Cancel edit</a><?php endif; ?>
        </form>

        <form class="toolbar with-button" method="get" style="margin-top:1.5rem">
          <label class="search-box"><span class="sr-only">Search students</span><input name="q" type="search" value="<?= h($q) ?>" placeholder="Search name, email or ID"></label>
          <label><span class="sr-only">Filter by course</span><select name="course"><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= h($c) ?>"<?= $courseFilter === $c ? ' selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select></label>
          <button class="btn" type="submit">Search / Filter</button>
        </form>

        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
<?php foreach ($students as $row): ?>
              <tr>
                <td><?= h($row['student_id']) ?></td><td><?= h($row['name']) ?></td><td><?= h($row['email']) ?></td><td><?= h((string)$row['mobile']) ?></td>
                <td><?= h($row['course']) ?></td><td><?= h($row['year']) ?></td><td><?= h($row['status']) ?></td>
                <td>
                  <div class="action-links">
                    <a class="btn btn-small btn-outline" href="?edit=<?= rawurlencode($row['student_id']) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this student record? Related registrations will also be deleted.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="student_id" value="<?= h($row['student_id']) ?>">
                      <button class="btn btn-small" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
<?php if (!$students): ?>
              <tr><td colspan="8">No student records found.</td></tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>
<?php
sh_dashboard_close();
sh_foot();
