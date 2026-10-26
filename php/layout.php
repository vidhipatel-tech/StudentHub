<?php
declare(strict_types=1);

/*
 * Shared page layout for every PHP page: <head>, vertical rail, top bar,
 * footer and the dashboard sidebar. Static .html pages use the same markup.
 */

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sh_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/** Path prefix to the project root: '' for root pages, '../' for pages inside php/. */
function sh_base(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return basename(dirname($script)) === 'php' ? '../' : '';
}

function sh_theme_toggle_icons(): string
{
    return '<svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>'
        . '<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
}

/** Vertical rail + top bar. $active is a page key such as 'home' or 'login'. */
function sh_nav(string $active, string $base): string
{
    $role = isset($_SESSION['user_id']) ? (string)($_SESSION['role'] ?? '') : '';
    $loggedIn = $role !== '';
    $showStudent = !$loggedIn || $role === 'student';
    $showAdmin = !$loggedIn || $role === 'admin';

    $link = static function (string $key, string $href, string $label) use ($active, $base): string {
        $class = $key === $active ? ' class="active"' : '';
        return '<a' . $class . ' href="' . h($base . $href) . '">' . h($label) . '</a>';
    };

    ob_start();
    ?>
  <a class="skip-link" href="#main">Skip to content</a>
  <aside class="side-nav" aria-label="Primary navigation">
    <a class="brand-mark" href="<?= h($base) ?>home.html" aria-label="StudentHub home">SH</a>
    <nav class="side-links" aria-label="Main sections">
      <?= $link('home', 'home.html', 'Home') ?>

      <?= $link('events', 'events.html', 'Events') ?>

      <button class="side-hub" id="sideHubBtn" type="button" aria-expanded="false" aria-controls="sideHubMenu">Hub</button>
      <?= $link('about', 'about.html', 'About') ?>

    </nav>
    <div class="side-bottom" aria-hidden="true">StudentHub</div>
    <div class="side-flyout" id="sideHubMenu">
      <?php if ($showStudent): ?><a href="<?= h($base) ?>student-dashboard.php">Student Dashboard</a><?php endif; ?>
      <?php if ($showAdmin): ?><a href="<?= h($base) ?>admin-dashboard.php">Admin Dashboard</a><?php endif; ?>
    </div>
  </aside>
  <header class="top-nav">
    <div class="top-nav-inner">
      <button class="hamburger" id="hamburger" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="topLinks">☰</button>
      <nav class="top-links" id="topLinks" aria-label="Top navigation">
        <?= $link('home', 'home.html', 'Home') ?>

        <?= $link('about', 'about.html', 'About') ?>

        <?= $link('events', 'events.html', 'Events') ?>

        <?= $link('contact', 'contact.html', 'Contact') ?>

        <?= $link('faq', 'faq.html', 'FAQ') ?>

        <?= $link('feedback', 'feedback.html', 'Feedback') ?>

        <div class="hub-menu-wrap">
          <button class="hub-button" id="hubButton" type="button" aria-expanded="false" aria-controls="hubMenu">Hub</button>
          <div class="hub-menu" id="hubMenu" hidden>
            <?php if ($showStudent): ?><a href="<?= h($base) ?>student-dashboard.php">Student Dashboard</a><?php endif; ?>
            <?php if ($showAdmin): ?><a href="<?= h($base) ?>admin-dashboard.php">Admin Dashboard</a><?php endif; ?>
          </div>
        </div>
        <?php if ($loggedIn): ?>
        <a href="<?= h($base) ?>php/logout.php">Logout</a>
        <?php else: ?>
        <?= $link('login', 'login.php', 'Login') ?>

        <a class="top-register<?= $active === 'register' ? ' active' : '' ?>" href="<?= h($base) ?>register.php">Register</a>
        <?php endif; ?>
      </nav>
      <button class="theme-button" id="themeButton" type="button" aria-label="Switch theme" aria-pressed="false"><?= sh_theme_toggle_icons() ?></button>
    </div>
  </header>
<?php
    return (string)ob_get_clean();
}

/**
 * Prints everything from <!DOCTYPE> to the opening <main>.
 * Options: title (required), active (nav key), description.
 */
function sh_head(array $opts): void
{
    sh_session();
    $base = sh_base();
    $title = (string)($opts['title'] ?? 'StudentHub');
    $active = (string)($opts['active'] ?? '');
    $description = (string)($opts['description'] ?? 'StudentHub student portal');
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="dark light">
  <meta name="description" content="<?= h($description) ?>">
  <title><?= h($title) ?> - StudentHub</title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%230b1220'/%3E%3Ctext x='32' y='44' font-family='Arial' font-size='30' font-weight='900' text-anchor='middle' fill='%23f97316'%3ESH%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?= h($base) ?>css/style.css">
  <script>try{var t=localStorage.getItem('studenthub-theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body data-nav="server">
<?= sh_nav($active, $base) ?>
  <main class="page" id="main">
<?php
}

/** Closes <main>, prints the footer and loads app.js. $inlineScript is optional raw JS. */
function sh_foot(string $inlineScript = ''): void
{
    $base = sh_base();
    ?>
  </main>
  <footer class="site-footer">StudentHub &nbsp;/&nbsp; Campus information, student services and events.</footer>
  <script src="<?= h($base) ?>js/app.js"></script>
<?php if ($inlineScript !== ''): ?>
  <script>
<?= $inlineScript ?>

  </script>
<?php endif; ?>
</body>
</html>
<?php
}

/** Sidebar entries for a dashboard. Hrefs are relative to the project root. */
function sh_dashboard_links(string $kind): array
{
    if ($kind === 'admin') {
        return [
            'dashboard'     => ['Dashboard', 'admin-dashboard.php'],
            'students'      => ['Students', 'php/admin-students.php'],
            'events'        => ['Events', 'php/admin-events.php'],
            'events-live'   => ['Live Events (AJAX)', 'php/admin-events-ajax.php'],
            'registrations' => ['Registrations', 'admin-dashboard.php#registrations'],
            'audit'         => ['Audit Log', 'admin-dashboard.php#audit'],
            'logout'        => ['Logout', 'php/logout.php'],
        ];
    }
    return [
        'dashboard'  => ['Dashboard', 'student-dashboard.php'],
        'profile'    => ['Profile', 'profile.html'],
        'events'     => ['Events', 'events.html'],
        'attendance' => ['Attendance', 'student-dashboard.php#attendance'],
        'results'    => ['Results', 'student-dashboard.php#results'],
        'courses'    => ['Courses', 'student-dashboard.php#courses'],
        'feedback'   => ['Feedback', 'feedback.html'],
        'logout'     => ['Logout', 'php/logout.php'],
    ];
}

/** Opens the dashboard grid: collapsible sidebar + main column. Pair with sh_dashboard_close(). */
function sh_dashboard_open(string $kind, string $active): void
{
    $base = sh_base();
    $label = $kind === 'admin' ? 'Admin dashboard navigation' : 'Student dashboard navigation';
    ?>
    <div class="dashboard-shell" id="dashboardShell">
      <aside class="dashboard-side">
        <button class="dashboard-brand-toggle" id="dashboardBrandToggle" type="button" aria-expanded="true" aria-controls="dashboardMenu" aria-label="Toggle dashboard menu">
          <span class="dashboard-brand-label">STUDENTHUB</span><span class="dashboard-toggle" aria-hidden="true">›</span>
        </button>
        <nav class="dashboard-menu" id="dashboardMenu" aria-label="<?= h($label) ?>">
<?php foreach (sh_dashboard_links($kind) as $key => [$text, $href]): ?>
          <a<?= $key === $active ? ' class="active" aria-current="page"' : '' ?> href="<?= h($base . $href) ?>"><?= h($text) ?></a>
<?php endforeach; ?>
        </nav>
      </aside>
      <div class="dashboard-main">
<?php
}

function sh_dashboard_close(): void
{
    echo "      </div>\n    </div>\n";
}

/** A full page with one message card (form results, access denied, errors). Ends the script. */
function sh_result_page(string $title, string $message, bool $success = true, int $status = 200, ?string $backHref = null, string $backLabel = 'Back to StudentHub'): never
{
    http_response_code($status);
    $backHref ??= sh_base() . 'home.html';
    sh_head(['title' => $title]);
    ?>
    <section class="section">
      <div class="container narrow">
        <div class="form-card result-card <?= $success ? 'success' : 'error' ?>">
          <div class="kicker">StudentHub</div>
          <h1><?= h($title) ?></h1>
          <p><?= h($message) ?></p>
          <a class="btn" href="<?= h($backHref) ?>"><?= h($backLabel) ?></a>
        </div>
      </div>
    </section>
<?php
    sh_foot();
    exit;
}
