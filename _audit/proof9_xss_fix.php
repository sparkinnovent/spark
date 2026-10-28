<?php
// Verify spark_safe_message(): script dies, exactly one mailto anchor survives.
require_once __DIR__ . '/../security.php';

$cases = [
    'xss via format'  => "Sorry. You have selected an unsupported format for <script>alert(1)</script>. Check.",
    'xss via session' => '<script>alert(3)</script>',
    'xss via img'     => '<img src=x onerror=alert(4)>',
    'svg payload'     => '<svg/onload=alert(5)>',
    'quote break'     => "\" onmouseover=\"alert(6)",
    'mailto single'   => "please do not hesitate to <a href='mailto:secretariatgdiid@uitm.edu.my'>contact us</a>.",
    'mailto double'   => 'please do not hesitate to <a href="mailto:secretariatgdiid@uitm.edu.my">contact us</a>.',
    'mailto inject'   => "<a href='mailto:x\" onmouseover=\"alert(1)'>click</a>",
    'mailto script'   => "<a href='mailto:a@b.com'><script>alert(7)</script></a>",
    'two links'       => "<a href='mailto:a@b.com'>one</a> and <a href='mailto:c@d.com'>two</a>",
];

$fails = 0;
foreach ($cases as $label => $input) {
    $out = spark_safe_message($input);

    // Only tags we emitted ourselves may appear.
    preg_match_all('/<(a)\b[^>]*>/i', $out, $anchors);
    $matchCount = count($anchors[0]);

    // Every emitted anchor must be a plain mailto we rebuilt.
    $badAnchor = 0;
    foreach ($anchors[0] as $a) {
        if (!preg_match('/^<a href="mailto:[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+">$/', $a)) {
            $badAnchor++;
        }
    }
    $hasScript = preg_match('/<(script|img|svg|iframe|object|body)\b/i', $out) === 1;
    $hasOnAttr = preg_match('/<[^>]*\bon[a-z]+\s*=/i', $out) === 1;

    $status = (!$hasScript && !$hasOnAttr && $badAnchor === 0) ? 'ok' : 'FAIL';
    if ($status === 'FAIL') { $fails++; }
    printf("%-15s %-4s anchors=%d bad=%d | %s\n", $label, $status, $matchCount, $badAnchor, substr($out, 0, 78));
}

// Positive control: the real message must still contain a working link.
$good = spark_safe_message($cases['mailto single']);
if (!str_contains($good, '<a href="mailto:secretariatgdiid@uitm.edu.my">contact us</a>')) {
    echo "FAIL  legitimate mailto anchor was not preserved\n";
    $fails++;
} else {
    echo "ok    legitimate mailto anchor preserved\n";
}

// And both links in the two-link case survive.
$two = spark_safe_message($cases['two links']);
if (substr_count($two, '<a href="mailto:') !== 2) {
    echo "FAIL  expected 2 anchors, got " . substr_count($two, '<a href="mailto:') . "\n";
    $fails++;
} else {
    echo "ok    two anchors preserved\n";
}

echo $fails === 0 ? "\nXSS SANITISER OK\n" : "\n$fails FAILURE(S)\n";
exit($fails ? 1 : 0);
