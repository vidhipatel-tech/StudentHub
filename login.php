<?php
declare(strict_types=1);

require_once __DIR__ . '/php/auth.php';

// Already signed in: go straight to the right dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: ' . appUrl(dashboardFor((string)($_SESSION['role'] ?? 'student'))));
    exit;
}

$loginMessage = (string)($_SESSION['login_message'] ?? '');
unset($_SESSION['login_message']);

sh_head(['title' => 'Login', 'active' => 'login']);
?>
    <header class="page-header">
      <div class="container">
        <div class="kicker">Student access</div>
        <h1>Login</h1>
        <p>Sign in to open your StudentHub dashboard.</p>
      </div>
    </header>
    <section class="section">
      <div class="container narrow">
        <?php if ($loginMessage !== ''): ?>
        <div class="message-box error" role="alert"><?= h($loginMessage) ?></div>
        <?php endif; ?>
        <form class="form-card" id="loginForm" action="php/authenticate.php" method="post" novalidate>
          <div class="form-group">
            <label for="loginEmail">Email</label>
            <input id="loginEmail" name="email" type="email" autocomplete="username" required>
            <small class="error" data-error-for="loginEmail"></small>
          </div>
          <div class="form-group">
            <label for="loginPassword">Password</label>
            <div class="input-wrap">
              <input id="loginPassword" name="password" type="password" autocomplete="current-password" required>
              <button class="password-toggle" type="button" data-password-toggle="loginPassword" aria-label="Show password">Show</button>
            </div>
            <small class="error" data-error-for="loginPassword"></small>
          </div>
          <label class="check-row"><input type="checkbox" id="rememberMe" name="remember" value="1"> Remember me on this device</label>
          <button class="btn" type="submit">Login</button>
          <p class="form-note">New student? <a href="register.php">Create an account</a>.</p>
        </form>
      </div>
    </section>
<?php sh_foot();
