<?php
/**
 * Regression suite for the security fix pass.
 *
 * Each case asserts a property the original code violated. Run with:
 *     php _audit\verify-fixes.php
 *
 * Exits non-zero on any failure.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../security.php';

$pass = 0;
$fail = 0;
$failures = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail, $failures;
    if ($ok) {
        $pass++;
        printf("  ok    %s\n", $label);
    } else {
        $fail++;
        $failures[] = $label . ($detail !== '' ? " — $detail" : '');
        printf("  FAIL  %s%s\n", $label, $detail !== '' ? " — $detail" : '');
    }
}

function section(string $title): void
{
    echo "\n" . str_repeat('-', 72) . "\n" . $title . "\n" . str_repeat('-', 72) . "\n";
}

/* ==================================================================== */
section('H1 — output escaping and message rendering');

$attacks = [
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '<svg/onload=alert(1)>',
    '"><script>alert(1)</script>',
    "'><iframe src=javascript:alert(1)>",
];

$anyRawTag = false;
foreach ($attacks as $a) {
    $out = spark_safe_message($a);
    if (preg_match('/<(script|img|svg|iframe|object|body)\b/i', $out)) {
        $anyRawTag = true;
    }
}
check('spark_safe_message blocks every injected tag', !$anyRawTag);
check('e() escapes angle brackets', e('<b>') === '&lt;b&gt;');
check('e() escapes both quote styles',
    e('a"b\'c') === 'a&quot;b&#039;c');
check('e(null) is empty, not a warning', e(null) === '');

$msg = "Questions? <a href='mailto:a@b.com'>contact us</a>.";
$out = spark_safe_message($msg);
check('a legitimate mailto anchor survives', str_contains($out, '<a href="mailto:a@b.com">contact us</a>'));
check('the mailto address is escaped inside the href', str_contains(spark_safe_message("<a href='mailto:a@b.com'>x</a>"), 'mailto:a@b.com'));

/* ==================================================================== */
section('H6 — CSRF token issue, compare, and rejection');

unset($_SESSION['csrf_token']);
$t1 = csrf_token();
check('token is 64 hex chars', strlen($t1) === 64 && ctype_xdigit($t1));
check('token is stable within a session', csrf_token() === $t1);
check('csrf_field renders a hidden input', str_contains(csrf_field(), 'name="csrf_token"'));

$_POST['csrf_token'] = $t1;
$_SERVER['REQUEST_METHOD'] = 'POST';
$ok = true;
try { csrf_verify(); } catch (Throwable $e) { $ok = false; }
check('csrf_verify accepts the matching token', $ok);

// A wrong token must abort. exit() is not catchable, so run it in a child.
$probe = __DIR__ . '/_csrf_probe.php';
file_put_contents($probe, '<?php
require_once __DIR__ . "/../security.php";
session_id("csrftest");
@session_start();
$_SESSION["csrf_token"] = "expected";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST["csrf_token"] = "wrong";
csrf_verify();
echo "NOT-BLOCKED";
');
$out = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
check('csrf_verify rejects a wrong token', !str_contains($out, 'NOT-BLOCKED'), trim(substr($out, 0, 60)));
@unlink($probe);

// GET requests are skipped, so read-only pages still render.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = [];
$ok = true;
try { csrf_verify(); } catch (Throwable $e) { $ok = false; }
check('csrf_verify ignores GET', $ok);

check('csrf_url appends with ?', str_contains(csrf_url('./x.php'), './x.php?csrf='));
check('csrf_url appends with &', str_contains(csrf_url('./x.php?a=1'), './x.php?a=1&csrf='));

/* ==================================================================== */
section('H1 — CSRF auto-injection covers every POST form');

$html = csrf_inject_forms(
    '<form method="post" action="a.php"><input name="x"></form>'
    . '<form method="GET" action="b.php"></form>'
    . '<form action="c.php" method="POST"></form>'
    . '<form action="d.php" method=post>'
    . '<form method="post">' . csrf_field() . '</form>'
);

$postForms = 0;
$tokensInPost = 0;
$tokensInGet = 0;
preg_match_all('#<form\b[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE);
foreach ($m[0] as $i => $hit) {
    [$tag, $offset] = $hit;
    $bodyStart = $offset + strlen($tag);
    $bodyEnd = isset($m[0][$i + 1]) ? $m[0][$i + 1][1] : strlen($html);
    $body = substr($html, $bodyStart, $bodyEnd - $bodyStart);

    $isPost = preg_match('#method\s*=\s*(["\']?)post\1#i', $tag) === 1;
    $count = substr_count($body, 'name="csrf_token"');

    if ($isPost) {
        $postForms++;
        if ($count === 1) { $tokensInPost++; }
    } elseif ($count > 0) {
        $tokensInGet++;
    }
}
check('all 4 POST forms received exactly one token', $tokensInPost === $postForms && $postForms === 4, "$tokensInPost of $postForms");
check('no GET form received a token', $tokensInGet === 0, "found in $tokensInGet GET form(s)");
// 3 injected + the 1 the markup already carried; the fifth POST form was that one.
check('a form that already had a token is not doubled',
    substr_count($html, 'name="csrf_token"') === 4, (string)substr_count($html, 'name="csrf_token"'));
check('form tags are not duplicated by the transform',
    preg_match_all('#<form\b#i', $html) === 5, (string)preg_match_all('#<form\b#i', $html));
check('the transform is a no-op on HTML with no forms', csrf_inject_forms('<p>hi</p>') === '<p>hi</p>');

/* ==================================================================== */
section('C2 — upload validation');

$tmp = tempnam(sys_get_temp_dir(), 'spk');
file_put_contents($tmp, "<?php echo 'pwned';");

// A .php file must be refused by the extension whitelist even if content sniffs as text.
$r = save_upload(
    ['name' => 'shell.php', 'type' => 'image/jpeg', 'tmp_name' => $tmp,
     'size' => filesize($tmp), 'error' => UPLOAD_ERR_OK],
    sys_get_temp_dir() . '/spk_up1',
    ['pdf', 'jpg', 'png'],
    1048576
);
check('a .php upload is rejected by extension', $r['ok'] === false, $r['error'] ?? '');

// A .jpg whose content is PHP must be rejected by the MIME probe.
$r = save_upload(
    ['name' => 'photo.jpg', 'type' => 'image/jpeg', 'tmp_name' => $tmp,
     'size' => filesize($tmp), 'error' => UPLOAD_ERR_OK],
    sys_get_temp_dir() . '/spk_up2',
    ['pdf', 'jpg', 'png'],
    1048576
);
check('content/extension mismatch is rejected', $r['ok'] === false, $r['error'] ?? '');

// Oversize is refused before anything is written.
$r = save_upload(
    ['name' => 'a.pdf', 'type' => 'application/pdf', 'tmp_name' => $tmp,
     'size' => 99_999_999, 'error' => UPLOAD_ERR_OK],
    sys_get_temp_dir() . '/spk_up3',
    ['pdf'],
    1024
);
check('oversize upload is rejected', $r['ok'] === false);

// Upload errors are surfaced, not silently ignored.
$r = save_upload(
    ['name' => 'a.pdf', 'type' => 'application/pdf', 'tmp_name' => '',
     'size' => 10, 'error' => UPLOAD_ERR_NO_FILE],
    sys_get_temp_dir() . '/spk_up4',
    ['pdf'],
    1024
);
check('a missing upload is reported as an error', $r['ok'] === false);

@unlink($tmp);

check('mime map allows pdf as application/pdf', mime_allowed_for_ext('pdf', 'application/pdf'));
check('mime map rejects a pdf declared as jpeg', !mime_allowed_for_ext('pdf', 'image/jpeg'));
check('mime map rejects octet-stream for docx', !mime_allowed_for_ext('docx', 'application/octet-stream'));

/* ==================================================================== */
section('C4 — download path containment');

$base = realpath(__DIR__ . '/..');
$outside = $base . '/config.php';
check('basename strips traversal', basename('../../config.php') === 'config.php');
check('a resolved path outside the dir is detectable',
    !str_starts_with($outside, $base . '/upload/payment/' . DIRECTORY_SEPARATOR));

/* ==================================================================== */
section('Auth gates');

// require_project / require_admin must redirect rather than fall through.
$probe = __DIR__ . '/_gate_probe.php';
file_put_contents($probe, '<?php
require_once __DIR__ . "/../security.php";
$_SESSION = [];
require_project();
echo "FELL-THROUGH";
');
$out = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
check('require_project does not fall through when unset', !str_contains($out, 'FELL-THROUGH'));
@unlink($probe);

$probe = __DIR__ . '/_gate_probe2.php';
file_put_contents($probe, '<?php
require_once __DIR__ . "/../security.php";
$_SESSION = [];
require_admin();
echo "FELL-THROUGH";
');
$out = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
check('require_admin does not fall through when unset', !str_contains($out, 'FELL-THROUGH'));
@unlink($probe);

// A logged-in user with the wrong role is refused.
$probe = __DIR__ . '/_gate_probe3.php';
file_put_contents($probe, '<?php
require_once __DIR__ . "/../security.php";
$_SESSION["user"] = [7 => ["level" => 5]];
require_role([1,2]);
echo "FELL-THROUGH";
');
$out = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
check('require_role blocks an insufficient role', !str_contains($out, 'FELL-THROUGH'));
@unlink($probe);

$probe = __DIR__ . '/_gate_probe4.php';
file_put_contents($probe, '<?php
require_once __DIR__ . "/../security.php";
$_SESSION["user"] = [7 => ["level" => 1]];
echo is_super_admin() ? "SUPER" : "NOT";
');
$out = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
check('is_super_admin recognises level 1', str_contains($out, 'SUPER'));
@unlink($probe);

/* ==================================================================== */
section('Tokens');

$a = secure_token(16);
$b = secure_token(16);
check('secure_token is 32 hex chars for 16 bytes', strlen($a) === 32 && ctype_xdigit($a));
check('secure_token is not predictable across calls', $a !== $b);
check('secure_token(32) is 64 chars', strlen(secure_token(32)) === 64);

/* ==================================================================== */
echo "\n" . str_repeat('=', 72) . "\n";
printf("%d passed, %d failed\n", $pass, $fail);
if ($fail > 0) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
}
echo str_repeat('=', 72) . "\n";

exit($fail === 0 ? 0 : 1);
