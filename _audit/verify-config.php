<?php
/**
 * Config template check.
 *
 * config.php is not in the repository, so nothing has ever verified that the
 * sample and the installer agree on what the application needs. This asserts
 * that every constant the code reads is present in the template, and that the
 * generated config the installer writes would contain the same set.
 *
 *   php _audit/verify-config.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$pass = 0;
$fail = 0;
$failures = [];

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

/*
 * Constants the application actually reads. Collected by scanning for
 * defined('X') guards and bare uses of the known prefixes.
 */
$required = [
    'DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME',
    'APP_URL', 'APP_VERSION',
    'RECAPTCHA_SECRET',
    'SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM_NAME',
];

echo "Config template check\n\n--- template completeness ---\n";

$samplePath = $root . '/config.sample.php';
$sample = is_file($samplePath) ? file_get_contents($samplePath) : '';
check('config.sample.php exists', $sample !== '');

$missing = [];
foreach ($required as $k) {
    // Match define('KEY', ...) anywhere in the template.
    if (preg_match("/define\(\s*'" . preg_quote($k, '/') . "'/", $sample) !== 1) {
        $missing[] = $k;
    }
}
check('every required constant appears in the template', $missing === [], implode(', ', $missing));

echo "\n--- the template parses and defines what it claims ---\n";

// Execute the template in a subprocess so a redefinition does not pollute this
// process, and so a syntax error is caught rather than fataling here.
$probe = __DIR__ . '/_cfg_probe.php';
$code = "<?php\n"
    . "require " . var_export($samplePath, true) . ";\n"
    . "\$out = [];\n"
    . "foreach (['" . implode("','", $required) . "'] as \$k) {\n"
    . "    \$out[\$k] = defined(\$k);\n"
    . "}\n"
    . "echo json_encode(\$out);\n";
file_put_contents($probe, $code);
$raw = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
@unlink($probe);

$defined = json_decode(trim($raw), true);
check('the template executes without error', is_array($defined), substr(trim($raw), 0, 80));

if (is_array($defined)) {
    $undef = array_keys(array_filter($defined, static fn($v) => $v !== true));
    check('every constant is defined at runtime', $undef === [], implode(', ', $undef));
}

echo "\n--- installer emits the same set ---\n";

$installer = (string)@file_get_contents($root . '/install/index.php');
check('installer source is readable', $installer !== '');

$emitMissing = [];
foreach ($required as $k) {
    if (preg_match("/define\('" . preg_quote($k, '/') . "'/", $installer) !== 1) {
        $emitMissing[] = $k;
    }
}
check('installer writes every required constant', $emitMissing === [], implode(', ', $emitMissing));

// The two that were silently absent before this fix.
check('installer writes RECAPTCHA_SECRET', str_contains($installer, "define('RECAPTCHA_SECRET'"));
check('installer writes APP_URL', str_contains($installer, "define('APP_URL'"));

echo "\n--- the template is not world-readable-by-URL by accident ---\n";

$htaccess = (string)@file_get_contents($root . '/.htaccess');
check('.htaccess denies config files',
    str_contains($htaccess, 'FilesMatch "^config(\\.sample)?\\.php$"'));

$build = (string)@file_get_contents($root . '/build-package.ps1');

// Look inside the exclusion lists only - the file names appear elsewhere in the
// script (config.sample.php is a REQUIRED file, the opposite of excluded), so a
// bare substring search proves nothing.
function build_list(string $script, string $var): string
{
    $needle = '$' . $var . ' = @(';
    $start  = strpos($script, $needle);
    if ($start === false) { return ''; }
    // The opening "@(" contains a paren; skip past it before hunting the close.
    $end = strpos($script, "\n)", $start + strlen($needle));
    if ($end === false) { return ''; }
    return substr($script, $start, $end - $start);
}

$xf = build_list($build, 'xf');   // excluded files
$xd = build_list($build, 'xd');   // excluded directories

check('build-package.ps1 exclusion lists were found', $xf !== '' && $xd !== '');
check('build-package.ps1 excludes config.php', str_contains($xf, "'config.php'"));
check('build-package.ps1 excludes _audit', str_contains($xd, "'_audit'"));
check('build-package.ps1 excludes _retired', str_contains($xd, "'_retired'"));

// The template has to travel with the package - the README points operators at it.
check('build-package.ps1 keeps config.sample.php', !str_contains($xf, "'config.sample.php'"));
check('build-package.ps1 treats config.sample.php as required',
    str_contains($build, "'config.sample.php', 'security.php', 'README.md'"));

echo "\n--- defensive reads: absent constants must not fatal ---\n";

/*
 * Dbcontroller, Mail, project.process and forget.php all check defined() before
 * use. Prove that by loading Mail.php with no config at all and calling the
 * constant-driven path.
 */
$probe = __DIR__ . '/_cfg_absent.php';
file_put_contents($probe, '<?php
require ' . var_export($root . '/Mail.php', true) . ';
// If the file defines fallbacks, these exist and nothing threw.
echo defined("SMTP_HOST") ? "FALLBACK-OK" : "NO-FALLBACK";
');
$out = shell_exec('php ' . escapeshellarg($probe) . ' 2>&1') ?? '';
@unlink($probe);
check('Mail.php supplies SMTP fallbacks when config is absent',
    str_contains($out, 'FALLBACK-OK'), substr(trim($out), 0, 80));

echo "\n" . str_repeat('=', 66) . "\n";
printf("%d passed, %d failed\n", $pass, $fail);
if ($fail) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) { echo "  - $f\n"; }
}
echo str_repeat('=', 66) . "\n";
exit($fail === 0 ? 0 : 1);
