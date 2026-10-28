<?php
// Bug: index.php line 45 uses $$msg_error (variable variable) instead of $msg_error.
// Proof that the pattern never sets the intended variable.
error_reporting(E_ALL);
ini_set('display_errors', '1');

$_GET = ['fail_connect' => 'Connection failed: Access denied', 'fail' => 'payment'];

if (isset($_GET["fail_connect"])) {
    $$msg_error = $_GET["fail"];
}

echo "isset(\$msg_error)        = " . var_export(isset($msg_error), true) . "\n";
echo "isset(\${\"\"})             = " . var_export(isset(${''}), true) . "\n";
$vars = get_defined_vars();
echo "user-defined vars        = " . implode(', ', array_keys($vars)) . "\n";
echo "\nResult: the connect-failure message is never rendered on index.php.\n";
