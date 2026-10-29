<?php
/**
 * End-to-end HTTP checks against a running dev server.
 *     php -S 127.0.0.1:8902 -t .   (from the project root)
 *     php _audit\verify-http.php
 */

declare(strict_types=1);

$base = $argv[1] ?? 'http://127.0.0.1:8902';

$pass = 0;
$fail = 0;
$failures = [];

function req(string $url, array $opts = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CUSTOMREQUEST  => $opts['method'] ?? 'GET',
        CURLOPT_HTTPHEADER     => ['Accept: text/html'],
    ]);
    if (isset($opts['post'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $loc  = curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: '';
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => (string)$body, 'location' => $loc, 'error' => $err];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail, $failures;
    if ($ok) { $pass++; printf("  ok    %s\n", $label); }
    else {
        $fail++;
        $failures[] = $label;
        printf("  FAIL  %s%s\n", $label, $detail !== '' ? " — $detail" : '');
    }
}

echo "Target: $base\n";

/* ------------------------------------------------------------------ */
echo "\n--- C1/C4: unauthenticated access must not yield documents ---\n";

foreach ([
    'download-pop.php'      => 'payment proof',
    'download-loa.php'      => 'letter of acceptance',
    'download.ea.php'       => 'extended abstract',
    'download.review.php'   => 'reviewer file',
    'download.turnitin.php' => 'similarity report',
] as $file => $what) {
    $r = req("$base/$file?file=1622458482.jpg");
    // Either refused, or redirected to sign-in — never a 200 with file bytes.
    $blocked = in_array($r['code'], [301, 302, 303, 307, 308, 400, 401, 403, 404, 419], true)
        || stripos($r['body'], 'DUMMY-PROOF') !== false && false;
    check("$file refuses anonymous access to $what", $blocked, 'HTTP ' . $r['code'] . ' len=' . strlen($r['body']));
}

/* ------------------------------------------------------------------ */
echo "\n--- C1/M2: admin handlers must not run for anonymous callers ---\n";

$adminPosts = [
    'admin/member.process.php' => ['add_j' => '1', 'member_name' => 'x', 'member_password' => 'y', 'member_role' => '1', 'member_email' => 'x@y.z'],
    'admin/fees.process.php'   => ['add' => '1', 'fees_name' => 'x', 'fees_rate' => '1'],
];

foreach ($adminPosts as $path => $fields) {
    $r = req("$base/$path", ['post' => $fields]);
    // A refusal redirect (to login) or an explicit error — never 'ok'.
    $blocked = in_array($r['code'], [301, 302, 303, 307, 308, 401, 403, 419], true)
        || trim($r['body']) !== 'ok';
    check("$path does not act on an anonymous POST", $blocked, 'HTTP ' . $r['code'] . ' body=' . substr(trim($r['body']), 0, 40));
}

/* ------------------------------------------------------------------ */
echo "\n--- H6: destructive endpoints reject GET ---\n";

foreach (['admin/deleteProject.php?id=1', 'admin/deleteUpload.php?id=1'] as $path) {
    $r = req("$base/$path");
    $blocked = in_array($r['code'], [301, 302, 307, 308, 401, 403, 405, 419], true);
    check("$path refuses a bare GET", $blocked, 'HTTP ' . $r['code']);
}

/* ------------------------------------------------------------------ */
echo "\n--- H1: reflected XSS must not survive ---\n";

$r = req("$base/admin/login.php?fail=" . urlencode('<script>alert(1)</script>'));
$raw = str_contains($r['body'], '<script>alert(1)</script>');
check('admin/login.php escapes ?fail=', !$raw, $raw ? 'payload found unescaped' : '');

$r = req("$base/index.php?fail=" . urlencode('<script>alert(2)</script>') . '&session_expired=1');
$raw = str_contains($r['body'], '<script>alert(2)</script>');
check('index.php escapes ?fail= on the session_expired branch', !$raw, $raw ? 'payload found unescaped' : '');

$r = req("$base/index.php?fail=file&format=" . urlencode('<script>alert(3)</script>'));
$raw = str_contains($r['body'], '<script>alert(3)</script>');
check('index.php escapes ?format=', !$raw, $raw ? 'payload found unescaped' : '');

/* ------------------------------------------------------------------ */
echo "\n--- L3: installer must not be reachable ---\n";

/*
 * The installer lock is enforced by .htaccess, which the PHP built-in server
 * ignores entirely. Assert the rule is present in the shipped config rather
 * than asserting HTTP behaviour this server cannot reproduce.
 *
 * FIX - the rule used to be unconditional, which made a fresh deployment
 * impossible: /install/ was denied before the operator could ever reach it.
 * It must now be gated on config.php, so the installer works exactly once.
 */
$htaccess = @file_get_contents(__DIR__ . '/../.htaccess');
check('the install directory is denied in .htaccess',
    $htaccess !== false && preg_match('#RewriteRule\s+\^install/\s+-\s+\[F,L\]#', $htaccess) === 1);
check('the install denial is gated on config.php, not unconditional',
    $htaccess !== false
    && preg_match('#RewriteCond\s+%\{DOCUMENT_ROOT\}/config\.php\s+-f\s*\r?\n\s*RewriteRule\s+\^install/#', $htaccess) === 1,
    'an unconditional rule makes the installer unreachable on a fresh deploy');
check('error_log is denied in .htaccess',
    $htaccess !== false && str_contains($htaccess, '<FilesMatch "^(error_log'));
check('backup and archive files are denied in .htaccess',
    $htaccess !== false && str_contains($htaccess, 'bak|old|orig'));
check('display_errors is off in .htaccess',
    $htaccess !== false && str_contains($htaccess, 'php_flag display_errors Off'));
check('config.php is denied in .htaccess',
    $htaccess !== false && str_contains($htaccess, 'FilesMatch "^config(\\.sample)?\\.php$"'));
check('the retired archive is denied in .htaccess',
    $htaccess !== false && str_contains($htaccess, 'RewriteRule ^_retired/ - [F,L]'));

/*
 * session.save_path must not be pinned to a version-specific directory. It was
 * set to /var/cpanel/php/sessions/ea-php73, which does not exist on a PHP 8
 * host; session_start() then failed silently and the installer's final button
 * did nothing. This application requires PHP 8.0+.
 */
check('.htaccess does not pin session.save_path to a PHP version',
    $htaccess !== false && !preg_match('#php_value\s+session\.save_path#', $htaccess),
    'a hard-coded save_path breaks sessions when the PHP version differs');

/*
 * install/ ships in the package but must only be reachable on a fresh deploy.
 * Two locks: the root rewrite blocks the path once config.php exists, and
 * install/.htaccess carries the same rule.
 */
$installHt = @file_get_contents(__DIR__ . '/../install/.htaccess');
check('install/.htaccess exists', $installHt !== false && $installHt !== '');
check('install/.htaccess denies .sql and .lock',
    $installHt !== false && str_contains($installHt, '\.(sql|lock)$'));
check('install/.htaccess is conditional on config.php existing',
    $installHt !== false && str_contains($installHt, 'RewriteCond %{DOCUMENT_ROOT}/config.php -f'));
check('install/.htaccess does not block a fresh install outright',
    $installHt !== false && !str_contains($installHt, 'RewriteRule ^index'),
    'an unconditional rule would make the installer unreachable');

/* ------------------------------------------------------------------ */
echo "\n--- installer: the final button must never fail silently ---\n";

/*
 * The "Install now" button posts step=3 and expects the settings to be waiting
 * in $_SESSION. If the session is gone the handler used to fall through with no
 * else branch: the same "Ready to install" page rendered again, no error, no
 * change, nothing in the log. It looked like a dead button.
 *
 * A POST with no session must now say so and send the operator back to the form.
 */
$r = req("$base/install/index.php", ['method' => 'POST', 'post' => ['step' => '3']]);
check('a step-3 POST with no session is not silently ignored',
    str_contains($r['body'], 'class="error"'),
    'no error was rendered - the button would look dead');
check('a step-3 POST with no session explains the cause',
    str_contains($r['body'], 'session'),
    'the message must name the session, not just fail');
check('a step-3 POST with no session returns to the settings form',
    str_contains($r['body'], 'name="db_host"'),
    'it must not re-render the confirmation page');
check('a step-3 POST with no session does not claim success',
    !str_contains($r['body'], 'Installation complete'));

// The requirements page must report session health before the form is filled in.
$r = req("$base/install/index.php");
check('the requirements page checks that sessions work',
    str_contains($r['body'], 'PHP session usable'),
    'a dead session was only discovered after the whole form was submitted');

/*
 * Every directory that receives an upload must carry its own guard. The build
 * script once preserved .htaccess and then deleted the folder holding it, which
 * shipped a package where an uploaded .php executed.
 */
$guarded = [
    'upload', 'upload/payment', 'upload/abstract', 'upload/review',
    'upload/turnitin', 'upload/certificate', 'upload/project', 'upload/receipt',
    'admin/upload', 'download',
];
$unguarded = [];
foreach ($guarded as $dir) {
    $abs = __DIR__ . '/../' . $dir;
    if (!is_dir($abs) || !is_file($abs . '/.htaccess')) {
        $unguarded[] = $dir;
    }
}
check('every upload directory carries a .htaccess guard', $unguarded === [], implode(', ', $unguarded));

// A guard that does not actually disable the engine is worse than none - it
// looks like protection.
$weak = [];
foreach ($guarded as $dir) {
    $abs = __DIR__ . '/../' . $dir . '/.htaccess';
    if (!is_file($abs)) { continue; }
    $g = (string)file_get_contents($abs);
    if (!str_contains($g, 'php_flag engine off')
        && !str_contains($g, 'RemoveHandler')
        && !str_contains($g, 'SetHandler')) {
        $weak[] = $dir;
    }
}
check('every guard actually disables the PHP engine', $weak === [], implode(', ', $weak));

$build = (string)@file_get_contents(__DIR__ . '/../build-package.ps1');
check('build refuses to ship a package without upload guards',
    str_contains($build, 'upload protection missing for'));
check('build refuses to ship participant files', str_contains($build, 'participant file(s) would ship'));
$ini = @file_get_contents(__DIR__ . '/../.user.ini');
check('display_errors is off in .user.ini',
    $ini !== false && str_contains($ini, 'display_errors = Off'));

/* ------------------------------------------------------------------ */
echo "\n--- L6: display_errors must be off ---\n";

$r = req("$base/admin/participant-upload.php");
$leak = (bool)preg_match('/(Fatal error|Warning:|Stack trace|Dbcontroller\.php|C:\\\\|\\/home2\\/)/', $r['body']);
check('no PHP error text or absolute path in a page', !$leak,
    $leak ? substr(preg_replace('/\s+/', ' ', $r['body']), 0, 90) : '');

/* ------------------------------------------------------------------ */
echo "\n" . str_repeat('=', 66) . "\n";
printf("%d passed, %d failed\n", $pass, $fail);
if ($fail) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) { echo "  - $f\n"; }
}
echo str_repeat('=', 66) . "\n";
exit($fail === 0 ? 0 : 1);
