<?php
// Prove admin/reset_password.php accepts an INVALID token.
// run() returns a mysqli_result object even when zero rows match,
// and the code does  if ($result) { ... }  which is always truthy.
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "1. Truthiness of a mysqli_result with 0 rows\n";
// emulate: $result is an object (mysqli_result), not a bool
$fakeResult = new stdClass();          // mysqli_result behaves the same for if()
echo "   if (\$result) evaluates to: " . var_export((bool)$fakeResult, true) . "\n";
echo "   -> the else-branch that redirects on a bad token is UNREACHABLE\n\n";

echo "2. Consequence: \$_SESSION['member_id'] is never populated for a bad token\n";
echo "   \$member_id = \$_SESSION[\"member_id\"];  -> undefined key\n\n";

echo "3. Password is mangled before hashing (line 29):\n";
$raw = "Pa'ss\\word1";
$mangled = addslashes($raw);           // == clean()
$hash = password_hash($mangled, PASSWORD_DEFAULT);
echo "   stored hash of mangled value; later login ALSO mangles -> verifies\n";
echo "   but password_verify(raw, hash) = " . var_export(password_verify($raw, $hash), true) . "\n";
echo "   -> reset succeeds, original plaintext never matches. Inconsistent with\n";
echo "      accounts created by insertMember() which hashes the RAW value.\n\n";

echo "4. Missing expiry: reset_token has no timestamp column.\n";
$sql = file_get_contents(__DIR__ . '/../uitmktco_gdiid25.sql');
preg_match('/CREATE TABLE `members` \((.*?)\n\)/s', $sql, $m);
echo "   members columns: " . implode(', ', array_map(
    fn($c) => trim($c, " `"),
    preg_split('/\n/', $m[1])
)) . "\n";
