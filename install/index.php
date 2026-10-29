<?php
/**
 * Spark Conference System - Web Installer
 *
 * Steps:
 *   1. Requirements check (PHP version, extensions, writable folders)
 *   2. Database + admin account form, connection test
 *   3. Import install/schema.sql, create admin member
 *   4. Write config.php and lock the installer
 *
 * Self-locking: creates install.lock once finished. Delete the
 * install/ folder on production servers after installation.
 */

declare(strict_types=1);

/*
 * FIX - error_reporting used to be set AFTER session_start(), so a failed
 * session start produced no visible warning. On cPanel the .htaccess forced
 * session.save_path to a PHP 7.3 directory; on a PHP 8 host that path does not
 * exist, session_start() failed silently, $_SESSION stayed empty, and the
 * "Install now" button did nothing at all. Turn errors on first.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// A dead session makes step 3 a silent no-op, so surface it here instead.
$sessionOk = session_status() === PHP_SESSION_ACTIVE;

define('APP_VERSION', '1.0.0');

$lockFile   = __DIR__ . '/install.lock';
$configFile = dirname(__DIR__) . '/config.php';
$rootPath   = dirname(__DIR__);

/* ------------------------------------------------------------------ *
 * Already installed?
 * ------------------------------------------------------------------ */
if (is_file($lockFile) || is_file($configFile)) {
    $step = 'locked';
} else {
    $step = $_POST['step'] ?? $_GET['step'] ?? '1';
    if (!in_array($step, ['1', '2', '3', 'done'], true)) {
        $step = '1';
    }
}

$errors  = [];
$notices = [];

/* ------------------------------------------------------------------ *
 * POST handlers
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === '2') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = (string)($_POST['db_pass'] ?? '');

    $adm_name  = trim($_POST['adm_name'] ?? '');
    $adm_email = trim($_POST['adm_email'] ?? '');
    $adm_pass  = (string)($_POST['adm_pass'] ?? '');
    $adm_pass2 = (string)($_POST['adm_pass2'] ?? '');

    // --- validation -------------------------------------------------
    if ($db_host === '')  $errors[] = 'Database host is required.';
    if ($db_name === '')  $errors[] = 'Database name is required.';
    if ($db_user === '')  $errors[] = 'Database user is required.';
    if (!preg_match('/^[A-Za-z0-9_]+$/', $db_name)) {
        $errors[] = 'Database name may only contain letters, numbers and underscores.';
    }
    if ($adm_name === '')      $errors[] = 'Admin username is required.';
    if (!filter_var($adm_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Admin email is not valid.';
    if (strlen($adm_pass) < 8) $errors[] = 'Admin password must be at least 8 characters.';
    if ($adm_pass !== $adm_pass2) $errors[] = 'Admin passwords do not match.';

    /*
     * FIX - APP_URL builds the password-reset link, so a wrong value produces
     * reset mails that point at the wrong host. Require an absolute http(s) URL
     * rather than letting a typo through to config.php.
     */
    $app_url_in = rtrim(trim($_POST['app_url'] ?? ''), '/');
    if ($app_url_in === '') {
        $errors[] = 'Public URL is required - password-reset links are built from it.';
    } elseif (!preg_match('#^https?://[^\s/]+#i', $app_url_in)) {
        $errors[] = 'Public URL must be an absolute http:// or https:// address, without a trailing slash.';
    }

    // --- connection test --------------------------------------------
    if (!$errors) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        if (!$conn) {
            $errors[] = 'Database connection failed: ' . mysqli_connect_error();
        } else {
            mysqli_close($conn);

            /*
             * FIX - assigning $_SESSION does not prove the session persisted.
             * With a dead session (unwritable save_path) the array is set in
             * memory, the page advances to "Ready to install", and the next POST
             * finds nothing. Write, then read back.
             */
            $_SESSION['install'] = [
                'db_host' => $db_host, 'db_name' => $db_name,
                'db_user' => $db_user, 'db_pass' => $db_pass,
                'adm_name' => $adm_name, 'adm_email' => $adm_email,
                'adm_pass' => $adm_pass,
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_user' => trim($_POST['smtp_user'] ?? ''),
                'smtp_pass' => (string)($_POST['smtp_pass'] ?? ''),
                'smtp_from' => trim($_POST['smtp_from'] ?? 'Conference Secretariat'),
                // FIX - these two were never collected, so they never reached
                // config.php and the features that need them degraded silently.
                'app_url' => $app_url_in,
                'recaptcha_secret' => trim($_POST['recaptcha_secret'] ?? ''),
            ];

            // Verify the session actually started - assigning to $_SESSION
            // succeeds even when there is no session, because it is just an
            // array in memory. Only session_status() tells the truth.
            if (session_status() !== PHP_SESSION_ACTIVE) {
                $errors[] = 'The server accepted your settings but could not start a PHP session, '
                    . 'so the next step would silently do nothing. '
                    . 'Check that session.save_path exists and is writable '
                    . '(cPanel > MultiPHP INI Editor), or ask your host to fix it.';
            } else {
                $step = '3'; // proceed to confirmation
            }
        }
    }

    if ($errors) {
        $step = '2';
    }
}

/*
 * FIX - this branch used to require !empty($_SESSION['install']) with no else.
 * If the session had expired or never started, the POST fell through to the
 * render block, which saw $step === '3' and printed the same "Ready to install"
 * page again. The button looked dead: no error, no change, nothing in the log.
 * A missing session is now an explicit, visible failure.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === '3') {
    if (empty($_SESSION['install'])) {
        $errors[] = 'Your installation session expired before the settings were saved. '
            . 'This usually means PHP could not write session data - check that '
            . 'session.save_path exists and is writable, then enter the settings again.';
        $step = '2';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['step'] ?? '') === '3'
    && !empty($_SESSION['install'])) {
    $c = $_SESSION['install'];

    // Defensive: a session created before the site/captcha fields were added
    // will not carry these keys.
    $c['app_url']          = $c['app_url'] ?? '';
    $c['recaptcha_secret'] = $c['recaptcha_secret'] ?? '';

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);
    if (!$conn) {
        $errors[] = 'Database connection failed: ' . mysqli_connect_error();
        $step = '2';
    } else {
        $conn->set_charset('utf8mb4');

        // --- run schema ---------------------------------------------
        $schemaFile = __DIR__ . '/schema.sql';
        if (!is_file($schemaFile)) {
            $errors[] = 'schema.sql not found in the install/ folder.';
        } else {
            $sql = file_get_contents($schemaFile);
            // Strip UTF-8 BOM if present
            if (substr($sql, 0, 3) === "\xEF\xBB\xBF") {
                $sql = substr($sql, 3);
            }
            // Remove comment-only lines, then split on statement boundaries.
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            // The dump contains no stored procedures/triggers, so a naive
            // splitter on ';' at end-of-line is sufficient.
            $statements = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));
            $imported   = 0;
            foreach ($statements as $stmt) {
                if ($stmt === '') continue;
                $res = mysqli_query($conn, $stmt);
                if ($res === false) {
                    // Table may already exist from a previous attempt - tolerate that.
                    if (str_contains(mysqli_error($conn), 'already exists')) {
                        $notices[] = 'Skipped existing: ' . substr($stmt, 0, 60) . '...';
                        continue;
                    }
                    $errors[] = 'SQL error: ' . mysqli_error($conn) . ' — near: ' . substr($stmt, 0, 80) . '...';
                    break;
                }
                $imported++;
            }
        }

        // --- create admin account -----------------------------------
        if (!$errors) {
            $stmt = $conn->prepare(
                'SELECT member_id FROM members WHERE member_email = ? LIMIT 1'
            );
            $stmt->bind_param('s', $c['adm_email']);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                // Admin exists (partial previous install) - update password instead.
                $hash = password_hash($c['adm_pass'], PASSWORD_DEFAULT);
                $upd = $conn->prepare(
                    'UPDATE members SET member_password = ?, member_name = ?, member_level = 1 WHERE member_email = ?'
                );
                $upd->bind_param('sss', $hash, $c['adm_name'], $c['adm_email']);
                if (!$upd->execute()) {
                    $errors[] = 'Could not update existing admin account: ' . $conn->error;
                } else {
                    $notices[] = 'Existing admin account updated with the new password.';
                }
            } else {
                $hash = password_hash($c['adm_pass'], PASSWORD_DEFAULT);
                $ins = $conn->prepare(
                    'INSERT INTO members (member_name, member_password, member_email, member_level)
                     VALUES (?, ?, ?, 1)'
                );
                $ins->bind_param('sss', $c['adm_name'], $hash, $c['adm_email']);
                if (!$ins->execute()) {
                    $errors[] = 'Could not create admin account: ' . $conn->error;
                }
            }
        }

        // --- write config.php ---------------------------------------
        if (!$errors) {
            /*
             * FIX - the generated config omitted RECAPTCHA_SECRET and APP_URL,
             * so a fresh install produced a site where registration ran with no
             * captcha and password-reset links pointed at the hardcoded fallback
             * host. Both constants are read through defined(), so nothing failed
             * loudly - the features just degraded. They are emitted now.
             */
            $appUrl = $c['app_url'] !== '' ? $c['app_url'] : 'https://gdiid.uitmkt.com';

            $config = "<?php\n"
                . "// Generated by the Spark installer on " . date('Y-m-d H:i:s') . "\n"
                . "// Keep this file secret. Do not commit it to version control.\n\n"
                . "define('DB_HOST', "     . var_export($c['db_host'], true) . ");\n"
                . "define('DB_USER', "     . var_export($c['db_user'], true) . ");\n"
                . "define('DB_PASSWORD', " . var_export($c['db_pass'], true) . ");\n"
                . "define('DB_NAME', "     . var_export($c['db_name'], true) . ");\n"
                . "define('APP_VERSION', " . var_export(APP_VERSION, true) . ");\n"
                . "\n// Public URL of this installation, no trailing slash.\n"
                . "// Used to build password-reset links.\n"
                . "define('APP_URL', " . var_export(rtrim($appUrl, '/'), true) . ");\n"
                . "\n// reCAPTCHA v2 SECRET key (not the site key). Read during registration.\n"
                . "// Leave empty to disable captcha; an error_log note is written each time.\n"
                . "define('RECAPTCHA_SECRET', " . var_export($c['recaptcha_secret'], true) . ");\n"
                . "\n// SMTP (email notifications). Edit these to match your mailbox.\n"
                . "define('SMTP_HOST', "    . var_export($c['smtp_host'] !== '' ? $c['smtp_host'] : 'mail.example.com', true) . ");\n"
                . "define('SMTP_USER', "    . var_export($c['smtp_user'] !== '' ? $c['smtp_user'] : 'no_reply@example.com', true) . ");\n"
                . "define('SMTP_PASS', "    . var_export($c['smtp_pass'], true) . ");\n"
                . "define('SMTP_FROM_NAME', " . var_export($c['smtp_from'] !== '' ? $c['smtp_from'] : 'Conference Secretariat', true) . ");\n";

            if (@file_put_contents($configFile, $config, LOCK_EX) === false) {
                $errors[] = 'Could not write config.php - check that the application root is writable.';
            } else {
                @chmod($configFile, 0640);
            }
        }

        // --- lock installer -----------------------------------------
        if (!$errors) {
            if (@file_put_contents($lockFile, 'Installed ' . date('c') . "\n") !== false) {
                unset($_SESSION['install']);
                $step = 'done';
            } else {
                $notices[] = 'Could not create install.lock (folder not writable). '
                    . 'Delete the install/ folder manually after this.';
                unset($_SESSION['install']);
                $step = 'done';
            }
        } else {
            $step = '3'; // stay on confirm step showing errors
        }

        mysqli_close($conn);
    }
}

/* ------------------------------------------------------------------ *
 * Step 1: requirements
 * ------------------------------------------------------------------ */
$checks = [];
if (!in_array($step, ['locked', 'done'], true)) {
    $phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
    $checks[] = ['PHP 7.4+ (found ' . PHP_VERSION . ')', $phpOk];
    foreach (['mysqli', 'gd', 'zip', 'openssl', 'fileinfo', 'mbstring', 'session'] as $ext) {
        $checks[] = ['PHP extension: ' . $ext, extension_loaded($ext)];
    }
    foreach (['upload', 'admin/upload', 'admin/dist'] as $dir) {
        $checks[] = ['Writable folder: ' . $dir . '/', is_writable($rootPath . '/' . $dir)];
    }
    $checks[] = ['Writable application root (for config.php)', is_writable($rootPath)];

    /*
     * FIX - the session store was never checked. The installer carries the
     * database credentials from step 2 to step 3 in $_SESSION, so a dead session
     * makes "Install now" a silent no-op. Catch it before the operator fills in
     * the whole form, not after.
     */
    $checks[] = ['PHP session usable (save_path: ' . (ini_get('session.save_path') ?: 'default') . ')',
        $sessionOk];
}
$checksPassed = !in_array(false, array_column($checks, 1), true);

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Spark Installer</title>
<style>
  :root { --brand:#2563eb; --ok:#16a34a; --bad:#dc2626; --bg:#f1f5f9; --card:#fff; --text:#0f172a; }
  * { box-sizing:border-box; }
  body { font-family:'Segoe UI',system-ui,sans-serif; background:var(--bg); color:var(--text); margin:0; padding:2rem 1rem; }
  .wrap { max-width:720px; margin:0 auto; }
  .card { background:var(--card); border-radius:12px; padding:2rem; box-shadow:0 4px 14px rgba(0,0,0,.08); margin-bottom:1.5rem; }
  h1 { font-size:1.5rem; margin:0 0 .25rem; }
  h1 span { color:var(--brand); }
  .sub { color:#64748b; margin:0 0 1.5rem; font-size:.95rem; }
  h2 { font-size:1.05rem; margin:1.5rem 0 .75rem; border-bottom:2px solid var(--bg); padding-bottom:.4rem; }
  label { display:block; font-weight:600; font-size:.85rem; margin:.9rem 0 .3rem; }
  input[type=text], input[type=email], input[type=password] {
    width:100%; padding:.6rem .75rem; border:1px solid #cbd5e1; border-radius:8px; font-size:.95rem;
  }
  input:focus { outline:2px solid var(--brand); border-color:var(--brand); }
  .row { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
  @media (max-width:560px){ .row { grid-template-columns:1fr; } }
  .btn { display:inline-block; background:var(--brand); color:#fff; border:none; border-radius:8px;
         padding:.7rem 1.6rem; font-size:1rem; font-weight:600; cursor:pointer; margin-top:1.5rem; text-decoration:none; }
  .btn:disabled { background:#94a3b8; cursor:not-allowed; }
  .btn-outline { background:transparent; color:var(--brand); border:2px solid var(--brand); padding:calc(.7rem - 2px) calc(1.6rem - 2px); }
  ul.checks { list-style:none; padding:0; margin:0; }
  ul.checks li { padding:.45rem 0; border-bottom:1px solid #f1f5f9; font-size:.92rem; display:flex; justify-content:space-between; }
  .ok  { color:var(--ok); font-weight:700; }
  .bad { color:var(--bad); font-weight:700; }
  ul.msgs { padding:0 1rem; }
  .error  { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:8px; padding:1rem 1.25rem; }
  .notice { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:8px; padding:1rem 1.25rem; }
  .steps { display:flex; gap:.4rem; margin-bottom:1.5rem; }
  .steps span { flex:1; height:5px; border-radius:3px; background:#e2e8f0; }
  .steps span.on { background:var(--brand); }
  .hint { font-size:.8rem; color:#64748b; margin-top:.2rem; }
  code { background:#f1f5f9; padding:.1rem .35rem; border-radius:4px; font-size:.85em; }
  .success-icon { font-size:3rem; text-align:center; color:var(--ok); }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1><span>Spark</span> Conference System Installer</h1>
    <p class="sub">Version <?= h(APP_VERSION) ?></p>

    <?php if ($step === 'locked'): ?>
      <div class="steps"><span class="on"></span><span class="on"></span><span class="on"></span><span class="on"></span></div>
      <div class="success-icon">&#10004;</div>
      <h2 style="text-align:center">Already installed</h2>
      <p style="text-align:center">This system has already been installed. The installer is locked for security.</p>
      <p style="text-align:center"><a class="btn btn-outline" href="../index.php">Go to the site</a></p>
      <p class="hint" style="text-align:center">If you really need to reinstall, remove <code>install/install.lock</code> and
      <code>config.php</code> — but this will not undo any database changes.</p>

    <?php elseif ($step === 'done'): ?>
      <div class="steps"><span class="on"></span><span class="on"></span><span class="on"></span><span class="on"></span></div>
      <div class="success-icon">&#10004;</div>
      <h2 style="text-align:center">Installation complete</h2>
      <?php foreach ($notices as $n): ?><p class="notice"><?= h($n) ?></p><?php endforeach; ?>
      <p><strong>Security: delete the <code>install/</code> folder now.</strong>
         The installer is locked, but removing the folder entirely is the safest option.</p>
      <p>Next steps:</p>
      <ul>
        <li>Log in at <a href="../admin/login.php"><code>admin/login.php</code></a> with the admin email and password you chose.</li>
        <li>Review <code>config.php</code> and adjust the SMTP settings if you skipped them.</li>
        <li>Set up your payment rates and event details in the admin panel.</li>
      </ul>
      <p style="text-align:center"><a class="btn" href="../admin/login.php">Go to admin login</a></p>

    <?php elseif ($step === '1'): ?>
      <div class="steps"><span class="on"></span><span></span><span></span><span></span></div>
      <h2>1. Server requirements</h2>
      <ul class="checks">
        <?php foreach ($checks as [$label, $ok]): ?>
          <li><?= h($label) ?> <span class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FAILED' ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($checksPassed): ?>
        <p style="margin-top:1.25rem">Everything looks good.</p>
        <a class="btn" href="?step=2">Continue &rarr;</a>
      <?php else: ?>
        <p class="error" style="margin-top:1rem">One or more requirements failed. Fix them (install PHP extensions
        via cPanel &gt; Select PHP Version, or make folders writable with permission 755/775) and reload this page.</p>
        <a class="btn btn-outline" href="?step=1">Re-check</a>
      <?php endif; ?>

    <?php elseif ($step === '2'): ?>
      <div class="steps"><span class="on"></span><span class="on"></span><span></span><span></span></div>
      <?php foreach ($errors as $e): ?><p class="error"><?= h($e) ?></p><?php endforeach; ?>
      <form method="post">
        <input type="hidden" name="step" value="2">
        <h2>2. Database connection</h2>
        <div class="row">
          <div>
            <label for="db_host">Database host</label>
            <input type="text" id="db_host" name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>" required>
          </div>
          <div>
            <label for="db_name">Database name</label>
            <input type="text" id="db_name" name="db_name" value="<?= h($_POST['db_name'] ?? '') ?>" required>
            <p class="hint">Must already exist (create it in cPanel &gt; MySQL Databases). Letters/numbers/underscores only.</p>
          </div>
        </div>
        <div class="row">
          <div>
            <label for="db_user">Database user</label>
            <input type="text" id="db_user" name="db_user" value="<?= h($_POST['db_user'] ?? '') ?>" required>
          </div>
          <div>
            <label for="db_pass">Database password</label>
            <input type="password" id="db_pass" name="db_pass" value="<?= h($_POST['db_pass'] ?? '') ?>">
          </div>
        </div>

        <h2>3. Admin account</h2>
        <p class="hint">This becomes the super-admin (<code>member_level = 1</code>) you use to log into <code>admin/</code>.</p>
        <div class="row">
          <div>
            <label for="adm_name">Admin username</label>
            <input type="text" id="adm_name" name="adm_name" value="<?= h($_POST['adm_name'] ?? '') ?>" required>
          </div>
          <div>
            <label for="adm_email">Admin email (used to log in)</label>
            <input type="email" id="adm_email" name="adm_email" value="<?= h($_POST['adm_email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="row">
          <div>
            <label for="adm_pass">Admin password (min. 8 chars)</label>
            <input type="password" id="adm_pass" name="adm_pass" required>
          </div>
          <div>
            <label for="adm_pass2">Repeat admin password</label>
            <input type="password" id="adm_pass2" name="adm_pass2" required>
          </div>
        </div>

        <h2>4. Site and captcha <small style="font-weight:400;color:#64748b">(required for registration and password reset)</small></h2>
        <div class="row">
          <div>
            <label for="app_url">Public URL of this installation</label>
            <input type="url" id="app_url" name="app_url" value="<?= h($_POST['app_url'] ?? '') ?>" placeholder="https://gdiid.uitmkt.com">
            <p class="hint">No trailing slash. Password-reset links are built from this.</p>
          </div>
          <div>
            <label for="recaptcha_secret">reCAPTCHA v2 secret key</label>
            <input type="text" id="recaptcha_secret" name="recaptcha_secret" value="<?= h($_POST['recaptcha_secret'] ?? '') ?>">
            <p class="hint">The secret key, not the site key. Leave blank to disable captcha.</p>
          </div>
        </div>

        <h2>5. Email (SMTP) settings <small style="font-weight:400;color:#64748b">(optional — can be edited later in config.php)</small></h2>
        <div class="row">
          <div>
            <label for="smtp_host">SMTP host</label>
            <input type="text" id="smtp_host" name="smtp_host" value="<?= h($_POST['smtp_host'] ?? '') ?>">
            <p class="hint">e.g. mail.yourdomain.com. Uses SSL port 465.</p>
          </div>
          <div>
            <label for="smtp_user">SMTP username (sender email)</label>
            <input type="text" id="smtp_user" name="smtp_user" value="<?= h($_POST['smtp_user'] ?? '') ?>">
          </div>
        </div>
        <div class="row">
          <div>
            <label for="smtp_pass">SMTP password</label>
            <input type="password" id="smtp_pass" name="smtp_pass" value="<?= h($_POST['smtp_pass'] ?? '') ?>">
          </div>
          <div>
            <label for="smtp_from">From name shown in emails</label>
            <input type="text" id="smtp_from" name="smtp_from" value="<?= h($_POST['smtp_from'] ?? 'Conference Secretariat') ?>">
          </div>
        </div>

        <button class="btn" type="submit">Test connection &amp; continue &rarr;</button>
      </form>
      <a class="btn btn-outline" href="?step=1" style="margin-left:.75rem">&larr; Back</a>

    <?php elseif ($step === '3'): ?>
      <div class="steps"><span class="on"></span><span class="on"></span><span class="on"></span><span></span></div>
      <?php foreach ($errors as $e): ?><p class="error"><?= h($e) ?></p><?php endforeach; ?>
      <?php foreach ($notices as $n): ?><p class="notice"><?= h($n) ?></p><?php endforeach; ?>
      <?php if (!$errors): ?>
        <h2>6. Ready to install</h2>
        <p>Connection successful. When you click <strong>Install now</strong> the installer will:</p>
        <ul>
          <li>Import all tables from <code>install/schema.sql</code> (empty, with role seed data)</li>
          <li>Create the super-admin account</li>
          <li>Write <code>config.php</code> in the application root</li>
          <li>Lock itself (<code>install/install.lock</code>)</li>
        </ul>
        <form method="post">
          <input type="hidden" name="step" value="3">
          <button class="btn" type="submit">Install now</button>
        </form>
        <a class="btn btn-outline" href="?step=2">&larr; Change settings</a>
      <?php else: ?>
        <a class="btn btn-outline" href="?step=2">&larr; Back to settings</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <p class="hint" style="text-align:center">Spark Conference System &middot; Installer v<?= h(APP_VERSION) ?></p>
</div>
</body>
</html>
