<?php
declare(strict_types=1);

require_once __DIR__ . '/php/auth.php';
requireRole('admin');

$userCount = (int)$mysqli->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$studentCount = (int)$mysqli->query('SELECT COUNT(*) FROM students')->fetch_row()[0];
$eventCount = (int)$mysqli->query('SELECT COUNT(*) FROM events')->fetch_row()[0];
$registrationCount = (int)$mysqli->query('SELECT COUNT(*) FROM registrations')->fetch_row()[0];

$latest = $mysqli->query(
    'SELECT r.registration_id, r.registered_at, s.student_id, s.name AS student_name, e.title AS event_title
     FROM registrations r
     JOIN students s ON s.student_id = r.student_id
     JOIN events e ON e.event_id = r.event_id
     ORDER BY r.registered_at DESC, r.registration_id DESC LIMIT 8'
)->fetch_all(MYSQLI_ASSOC);

$auditRows = $mysqli->query(
    'SELECT a.action, a.entity_type, a.entity_id, a.details, a.created_at, u.username
     FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id
     ORDER BY a.created_at DESC, a.audit_id DESC LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);

sh_head(['title' => 'Admin Dashboard']);
sh_dashboard_open('admin', 'dashboard');
?>
        <div class="dashboard-title">
          <div>
            <div class="kicker">Administration</div>
            <h1>Admin Dashboard</h1>
          </div>
          <span class="muted">Signed in: <?= h((string)($_SESSION['username'] ?? 'admin')) ?></span>
        </div>

        <div class="stats-grid">
          <div class="stat"><div class="stat-label">User Accounts</div><div class="stat-value"><?= $userCount ?></div></div>
          <div class="stat"><div class="stat-label">Students</div><div class="stat-value"><?= $studentCount ?></div></div>
          <div class="stat"><div class="stat-label">Events</div><div class="stat-value"><?= $eventCount ?></div></div>
          <div class="stat"><div class="stat-label">Registrations</div><div class="stat-value"><?= $registrationCount ?></div></div>
        </div>

        <section class="dashboard-section">
          <h2>Quick Actions</h2>
          <div class="action-links">
            <a class="btn" href="php/admin-students.php">Manage Students</a>
            <a class="btn" href="php/admin-events.php">Manage Events</a>
            <a class="btn btn-outline" href="php/admin-events-ajax.php">Live Events (AJAX)</a>
            <a class="btn btn-outline" href="php/db-test.php">Database Check</a>
          </div>
        </section>

        <section class="dashboard-section" id="registrations">
          <h2>Latest Registrations</h2>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Registration</th><th>Student ID</th><th>Name</th><th>Event</th><th>Registered At</th></tr></thead>
              <tbody>
<?php foreach ($latest as $row): ?>
                <tr><td><?= h((string)$row['registration_id']) ?></td><td><?= h($row['student_id']) ?></td><td><?= h($row['student_name']) ?></td><td><?= h($row['event_title']) ?></td><td><?= h($row['registered_at']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$latest): ?>
                <tr><td colspan="5">No registrations yet.</td></tr>
<?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="dashboard-section" id="audit">
          <h2>Recent Audit Log</h2>
          <p class="muted">Adding, editing and deleting students or events is recorded here.</p>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Area</th><th>Record</th><th>Details</th></tr></thead>
              <tbody>
<?php foreach ($auditRows as $row): ?>
                <tr><td><?= h($row['created_at']) ?></td><td><?= h($row['username'] ?? 'Deleted user') ?></td><td><?= h($row['action']) ?></td><td><?= h($row['entity_type']) ?></td><td><?= h($row['entity_id'] ?? '—') ?></td><td><?= h($row['details'] ?? '') ?></td></tr>
<?php endforeach; ?>
<?php if (!$auditRows): ?>
                <tr><td colspan="6">No actions recorded yet. Add, edit or delete a student or event to create an entry.</td></tr>
<?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
<?php
sh_dashboard_close();
sh_foot();
