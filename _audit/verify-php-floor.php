<?php
/**
 * PHP version floor check.
 *
 * The README claims a minimum PHP version. This proves it by scanning for the
 * newest language feature and function the codebase actually uses, rather than
 * guessing.
 *
 *   php _audit/verify-php-floor.php
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

/** Strip comments and strings so keyword matches are real code, not prose. */
function code_only(string $src): string
{
    $out = '';
    $len = strlen($src);
    $i   = 0;
    while ($i < $len) {
        $two = substr($src, $i, 2);
        if ($two === '/*') {
            $end = strpos($src, '*/', $i + 2);
            $i = $end === false ? $len : $end + 2;
            continue;
        }
        if ($two === '//') {
            $end = strpos($src, "\n", $i);
            $i = $end === false ? $len : $end;
            continue;
        }
        if ($src[$i] === '#') {
            $end = strpos($src, "\n", $i);
            $i = $end === false ? $len : $end;
            continue;
        }
        if ($src[$i] === "'" || $src[$i] === '"') {
            $q = $src[$i];
            $i++;
            while ($i < $len) {
                if ($src[$i] === '\\') { $i += 2; continue; }
                if ($src[$i] === $q) { $i++; break; }
                $i++;
            }
            continue;
        }
        $out .= $src[$i];
        $i++;
    }
    return $out;
}

$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
        $path = $f->getPathname();
        // Exclude dependencies and the retired archive.
        if (preg_match('#[\\\\/](vendor|_retired|_audit|release)[\\\\/]#', $path)) {
            continue;
        }
        $files[] = $path;
    }
}

printf("Scanned %d application files.\n\n", count($files));

/* ------------------------------------------------------------------ */
echo "--- feature detection ---\n";

$found = [];   // feature => [files]

$features = [
    // feature label           => [regex, minimum version]
    'str_contains / str_starts_with / str_ends_with' => ['/\b(str_contains|str_starts_with|str_ends_with)\s*\(/', '8.0'],
    'match expression'                               => ['/\bmatch\s*\(/', '8.0'],
    'nullsafe operator (?->)'                        => ['/\?->/', '8.0'],
    'constructor promotion'                          => ['/function\s+__construct\s*\([^)]*\b(public|private|protected)\s+/', '8.0'],
    'enum declaration'                               => ['/^\s*enum\s+\w+/m', '8.1'],
    'readonly property'                              => ['/\breadonly\s+(public|private|protected|[A-Za-z_\\\\]+\s+\$)/', '8.1'],
    'never return type'                              => ['/\)\s*:\s*never\b/', '8.1'],
    'first-class callable syntax'                    => ['/\w+\s*\(\.\.\.\)/', '8.1'],
    'readonly class'                                 => ['/\breadonly\s+class\s/', '8.2'],
    'DNF types'                                      => ['/\(\s*[A-Za-z_\\\\]+\s*&\s*[A-Za-z_\\\\]+\s*\)\s*\|/', '8.2'],
    'json_validate()'                                => ['/\bjson_validate\s*\(/', '8.3'],
];

$floor = '7.4';

foreach ($features as $label => [$re, $min]) {
    $where = [];
    foreach ($files as $path) {
        $src = @file_get_contents($path);
        if ($src === false) { continue; }
        if (preg_match($re, code_only($src))) {
            $where[] = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        }
    }
    if ($where) {
        $found[$label] = ['min' => $min, 'files' => $where];
        if (version_compare($min, $floor, '>')) {
            $floor = $min;
        }
        printf("  %-46s %-4s  %s\n", $label, $min, count($where) . ' file(s)');
    }
}

if (!$found) {
    echo "  (no version-sensitive feature detected above 7.4)\n";
}

/* ------------------------------------------------------------------ */
echo "\n--- conclusion ---\n";

printf("  detected floor: PHP %s\n", $floor);
check('the detected floor is at least 8.0',
    version_compare($floor, '8.0', '>='), $floor);

// The README must agree with what was detected.
$readme = (string)@file_get_contents($root . '/README.md');
check('README.md is readable', $readme !== '');

check('README states the detected floor', 
    preg_match('/\|\s*PHP\s*\|\s*' . preg_quote($floor, '/') . '\s*\|/', $readme) === 1,
    "README does not declare PHP $floor");

check('README warns that 7.4 will not run',
    str_contains($readme, '7.4 will not run'));

// The running interpreter must satisfy the floor.
check('the running PHP satisfies the floor',
    version_compare(PHP_VERSION, $floor, '>='), PHP_VERSION);

/* ------------------------------------------------------------------ */
echo "\n--- runtime check ---\n";
foreach (['str_contains', 'str_starts_with'] as $fn) {
    check("$fn() is available", function_exists($fn));
}

echo "\n" . str_repeat('=', 66) . "\n";
printf("%d passed, %d failed\n", $pass, $fail);
if ($fail) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) { echo "  - $f\n"; }
}
echo str_repeat('=', 66) . "\n";
exit($fail === 0 ? 0 : 1);
