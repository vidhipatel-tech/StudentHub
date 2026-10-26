<?php

declare(strict_types=1);

require_once __DIR__ . '/php/auth.php';
requireRole('student');

$email = (string)($_SESSION['email'] ?? '');
$name = (string)($_SESSION['name'] ?? 'Student');

// Student record linked to this account (matched by email)
$stmt = $mysqli->prepare('SELECT student_id, course, year, status FROM students WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc() ?: null;

// Upcoming events, straight from the database
$upcoming = $mysqli->query('SELECT event_id, title, category, event_date, event_time, venue, image FROM events WHERE event_date >= CURDATE() ORDER BY event_date, event_id LIMIT 4')->fetch_all(MYSQLI_ASSOC);
$upcomingTotal = (int)$mysqli->query('SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()')->fetch_row()[0];

// Events this student registered for
$myEvents = [];
if ($student) {
  $reg = $mysqli->prepare('SELECT e.title, e.event_date, e.venue FROM registrations r JOIN events e ON e.event_id = r.event_id WHERE r.student_id = ? ORDER BY e.event_date');
  $reg->bind_param('s', $student['student_id']);
  $reg->execute();
  $myEvents = $reg->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Subject lists per branch. Add more branches here when they are published.
$subjectsByCourse = [
  'B.Tech IT' => ['FCN', 'FDSA', 'WDF', 'OOP JAVA'],
];
$subjects = $student ? ($subjectsByCourse[$student['course']] ?? []) : [];

$firstName = explode(' ', trim($name))[0] ?: 'Student';
$subtitle = $student ? strtoupper($student['course'] . ' / ' . $student['year']) : 'STUDENT ACCOUNT';

function posterSrc(string $image): string
{
  if ($image === '') return 'images/events/event-01.png';
  return str_starts_with($image, 'uploads/') ? 'php/' . $image : $image;
}

sh_head(['title' => 'Student Dashboard']);
sh_dashboard_open('student', 'dashboard');
?>
<div class="dashboard-title">
  <div>
    <div class="kicker">Student space</div>
    <h1>Welcome, <?= h($firstName) ?></h1>
  </div>
  <div class="dashboard-identity">
    <div class="avatar" aria-label="Profile photo">
      <span id="dashboardAvatarInitial"><?= h(strtoupper(mb_substr($firstName, 0, 1))) ?></span>
      <img id="dashboardAvatarImg" alt="Your profile photo" hidden>
    </div>
    <span class="muted"><?= h($subtitle) ?></span>
  </div>
</div>

<div class="stats-grid">
  <div class="stat">
    <div class="stat-label">Student ID</div>
    <div class="stat-value"><?= h($student['student_id'] ?? '—') ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Events Registered</div>
    <div class="stat-value"><?= count($myEvents) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Upcoming Events</div>
    <div class="stat-value"><?= $upcomingTotal ?></div>
  </div>
</div>

<section class="dashboard-section" id="courses">
  <h2>My Courses</h2>
  <?php if ($subjects): ?>
    <p class="muted"><?= h($student['course']) ?> / Semester 3</p>
    <div class="course-grid">
      <?php foreach ($subjects as $subject): ?>
        <article class="course-card"><span class="course-code">Subject</span>
          <h3><?= h($subject) ?></h3>
          <p class="muted">Semester 3</p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="empty-note">The subject list for your branch has not been published yet.</p>
  <?php endif; ?>
</section>

<section class="dashboard-section" id="attendance">
  <h2>Attendance</h2>
  <p class="empty-note">No attendance has been recorded for you yet. It will appear here once your department publishes it.</p>
</section>

<section class="dashboard-section" id="results">
  <h2>Results</h2>
  <p class="empty-note">No results have been published for you yet.</p>
</section>

<section class="dashboard-section">
  <h2>My Event Registrations</h2>
  <?php if ($myEvents): ?>
    <div class="registration-list">
      <?php foreach ($myEvents as $row): ?>
        <div><strong><?= h(date('d M Y', strtotime($row['event_date']))) ?></strong><span><?= h($row['title']) ?></span><span><?= h($row['venue']) ?></span></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="empty-note">You have not registered for any event yet. <a href="events.html">Browse events</a>.</p>
  <?php endif; ?>
</section>

<section class="dashboard-section">
  <h2>Upcoming Events</h2>
  <?php if ($upcoming): ?>
    <div class="event-grid compact">
      <?php foreach ($upcoming as $e): ?>
        <article class="event-card">
          <span class="event-number"><?= h(strtoupper($e['category'])) ?></span>
          <div class="event-image"><img src="<?= h(posterSrc((string)$e['image'])) ?>" alt="<?= h($e['title']) ?> poster" loading="lazy"></div>
          <h3><?= h($e['title']) ?></h3>
          <p class="event-meta"><?= h(date('d M Y', strtotime($e['event_date']))) ?> / <?= h($e['event_time']) ?> / <?= h($e['venue']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="empty-note">No upcoming events right now.</p>
  <?php endif; ?>
</section>
<?php
sh_dashboard_close();
sh_foot();
