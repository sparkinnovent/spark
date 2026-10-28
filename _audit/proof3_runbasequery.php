<?php
// Bug: Dbcontroller::runBaseQuery() has no failure handling.
//   $result = mysqli_query(...);
//   while ($row = mysqli_fetch_assoc($result)) { ... }
// If the query fails mysqli_query returns false, and mysqli_fetch_assoc(false)
// is a TypeError on PHP 8 -> uncaught fatal, blank page.
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "mysqli_fetch_assoc(false) behaviour on PHP " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . ":\n";

try {
    $r = @mysqli_fetch_assoc(false);
    echo "  returned " . var_export($r, true) . "\n";
} catch (Throwable $e) {
    echo "  UNCAUGHT " . get_class($e) . ": " . $e->getMessage() . "\n";
}

echo "\nSecond defect in the same function: \$resultset is never initialised.\n";
$result = null;
// replicate the function body with zero rows
$rows = [];
if (!empty($resultset)) {          // never reached, but the read itself warns
    $rows = $resultset;
}
echo "  after loop with no rows, return value = " . var_export(empty($resultset) ? null : $resultset, true) . "\n";
echo "  callers do if(!empty(\$result)) -> treats 'no rows' and 'query failed' identically.\n";
