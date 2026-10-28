<?php
// Bug: admin/login.process.php passes $user (false from mysqli_fetch_assoc)
// straight into password_verify() without checking the row exists.
// On PHP 8 this is a TypeError / warning path, not a clean "invalid login".
error_reporting(E_ALL);
ini_set('display_errors', '1');

// mysqli_fetch_assoc() on a result with zero rows returns null.
$user = null;

echo "Simulating login with an email that does not exist in `members`:\n\n";

try {
    $ok = password_verify('whatever', $user["member_password"]);
    echo "password_verify returned: " . var_export($ok, true) . "\n";
} catch (Throwable $e) {
    echo "UNCAUGHT " . get_class($e) . ": " . $e->getMessage() . "\n";
}

echo "\nAlso: \$db_handle->clean() is applied to the PLAIN password before verify.\n";
$raw = "O'Brien2024!";
// mysqli_real_escape_string escapes the quote:
$escaped = addslashes($raw);          // same effect for this character
$hash = password_hash($raw, PASSWORD_DEFAULT);
echo "  raw password verified    = " . var_export(password_verify($raw, $hash), true) . "\n";
echo "  escaped password verified= " . var_export(password_verify($escaped, $hash), true) . "\n";
echo "\nResult: any password containing ' \" or \\ can never authenticate.\n";
