<?php
declare(strict_types=1);

require_once __DIR__ . '/php/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . appUrl(dashboardFor((string)($_SESSION['role'] ?? 'student'))));
    exit;
}

$courses = ['B.Tech IT', 'B.Tech CE', 'B.Tech CSE'];
$years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$genders = ['Female', 'Male', 'Other'];

$old = ['name' => '', 'username' => '', 'email' => '', 'mobile' => '', 'course' => '', 'year' => '', 'gender' => ''];
$errors = [];
$created = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $old['email'] = strtolower($old['email']);
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!csrfValid()) $errors[] = 'The form expired. Refresh the page and try again.';
    if (!preg_match('/^\p{L}[\p{L} .\'-]{1,49}$/u', $old['name'])) $errors[] = 'Enter a valid full name.';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $old['username'])) $errors[] = 'Username must be 3–30 letters, numbers, dots, underscores or hyphens.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[6-9][0-9]{9}$/', $old['mobile'])) $errors[] = 'Enter a valid 10-digit mobile number.';
    if (!in_array($old['course'], $courses, true)) $errors[] = 'Select a valid branch.';
    if (!in_array($old['year'], $years, true)) $errors[] = 'Select a valid year.';
    if (!in_array($old['gender'], $genders, true)) $errors[] = 'Select a gender.';
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/', $password)) $errors[] = 'Password must be 8–64 characters with uppercase, lowercase, number and special character.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($_POST['terms'])) $errors[] = 'Please confirm that the information is correct.';

    if (!$errors) {
        $check = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $check->bind_param('ss', $old['email'], $old['username']);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $errors[] = 'That email or username is already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $mysqli->begin_transaction();
        try {
            // 1. Login account
            $stmt = $mysqli->prepare("INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, 'student')");
            $stmt->bind_param('ssss', $old['name'], $old['username'], $old['email'], $hash);
            $stmt->execute();

            // 2. Student record, unless the administrator already created one for this email
            $find = $mysqli->prepare('SELECT student_id FROM students WHERE email = ? LIMIT 1');
            $find->bind_param('s', $old['email']);
            $find->execute();
            if (!$find->get_result()->fetch_assoc()) {
                $next = (int)$mysqli->query("SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)), 0) + 1 FROM students WHERE student_id REGEXP '^STU[0-9]+$'")->fetch_row()[0];
                $studentId = sprintf('STU%03d', $next);
                $ins = $mysqli->prepare('INSERT INTO students (student_id, name, email, mobile, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $ins->bind_param('sssssss', $studentId, $old['name'], $old['email'], $old['mobile'], $old['course'], $old['year'], $old['gender']);
                $ins->execute();
            }
            $mysqli->commit();
            $created = true;
            $old = array_fill_keys(array_keys($old), '');
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            $errors[] = 'The account could not be created. The email or username may already be in use.';
        }
    }
}

sh_head(['title' => 'Register', 'active' => 'register']);
?>
    <header class="page-header">
      <div class="container">
        <div class="kicker">Create your student account</div>
        <h1>Register</h1>
        <p>Enter your student details to create a StudentHub account.</p>
      </div>
    </header>
    <section class="section">
      <div class="container">
<?php if ($created): ?>
        <div class="form-card result-card success">
          <div class="kicker">Account created</div>
          <h1>Welcome aboard</h1>
          <p>Your StudentHub account is ready. You can log in now.</p>
          <a class="btn" href="login.php">Go to Login</a>
        </div>
<?php else: ?>
        <?php if ($errors): ?>
        <div class="message-box error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form class="form-card registration-form" id="registrationForm" action="register.php" method="post" novalidate>
          <?= csrfField() ?>

          <div class="form-grid">
            <div class="form-group">
              <label for="regName">Full Name</label>
              <input id="regName" name="name" type="text" autocomplete="name" value="<?= h($old['name']) ?>" required>
              <small class="error" data-error-for="regName"></small>
            </div>
            <div class="form-group">
              <label for="regUsername">Username</label>
              <input id="regUsername" name="username" type="text" autocomplete="username" value="<?= h($old['username']) ?>" required>
              <small class="error" data-error-for="regUsername"></small>
            </div>
            <div class="form-group">
              <label for="regEmail">Email</label>
              <input id="regEmail" name="email" type="email" autocomplete="email" value="<?= h($old['email']) ?>" required>
              <small class="error" data-error-for="regEmail"></small>
            </div>
            <div class="form-group">
              <label for="regMobile">Mobile Number</label>
              <input id="regMobile" name="mobile" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" value="<?= h($old['mobile']) ?>" required>
              <small class="error" data-error-for="regMobile"></small>
            </div>
            <div class="form-group">
              <label for="regCourse">Course</label>
              <select id="regCourse" name="course" required>
                <option value="">Select branch</option>
                <?php foreach ($courses as $c): ?><option<?= $old['course'] === $c ? ' selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
              </select>
              <small class="error" data-error-for="regCourse"></small>
            </div>
            <div class="form-group">
              <label for="regYear">Year</label>
              <select id="regYear" name="year" required>
                <option value="">Select year</option>
                <?php foreach ($years as $y): ?><option<?= $old['year'] === $y ? ' selected' : '' ?>><?= h($y) ?></option><?php endforeach; ?>
              </select>
              <small class="error" data-error-for="regYear"></small>
            </div>
            <fieldset class="form-group">
              <legend>Gender</legend>
              <div class="inline-options">
                <?php foreach ($genders as $g): ?><label><input type="radio" name="gender" value="<?= h($g) ?>"<?= $old['gender'] === $g ? ' checked' : '' ?>> <?= h($g) ?></label><?php endforeach; ?>
              </div>
              <small class="error" data-error-for="gender"></small>
            </fieldset>
            <div class="form-group">
              <label for="regPassword">Password</label>
              <div class="input-wrap">
                <input id="regPassword" name="password" type="password" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle="regPassword" aria-label="Show password">Show</button>
              </div>
              <div class="strength" id="strengthMeter" data-level="0"><span id="strengthBar"></span></div>
              <small class="strength-text" id="strengthText">Use 8+ characters with uppercase, lowercase, number and special character.</small>
              <small class="error" data-error-for="regPassword"></small>
            </div>
            <div class="form-group">
              <label for="regConfirm">Confirm Password</label>
              <div class="input-wrap">
                <input id="regConfirm" name="confirm_password" type="password" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle="regConfirm" aria-label="Show password">Show</button>
              </div>
              <small class="error" data-error-for="regConfirm"></small>
            </div>
          </div>
          <label class="check-row"><input id="regTerms" name="terms" type="checkbox" value="1"> I confirm that the information entered is correct.<small class="error" data-error-for="regTerms"></small></label>
          <button class="btn" type="submit">Create Account</button>
          <p class="form-note">Already registered? <a href="login.php">Log in</a>.</p>
        </form>
<?php endif; ?>
      </div>
    </section>
<?php sh_foot();
