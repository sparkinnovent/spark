<?php
// Proof that index.php lines 24-109 assign raw $_GET values into $msg_error,
// and line 191 echoes $msg_error with no escaping.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Replicate the exact block from index.php verbatim.
$_GET = ['fail' => '<script>alert(3)</script>', 'session_expired' => '1'];
$msg_error = null;

if (isset($_GET["fail"])) {
    $fail = $_GET["fail"];
    if ($fail == "file") { /* ... */ }
    if (isset($_GET["session_expired"])) {
        $msg_error = $_GET["fail"];
    }
}
echo "branch: session_expired\n";
echo "  \$msg_error = " . var_export($msg_error, true) . "\n";
echo "  rendered   = " . $msg_error . "\n\n";

// Second vector: the ?format= reflection.
$_GET = ['fail' => 'file', 'format' => '<script>alert(4)</script>'];
$msg_error = null;
if (isset($_GET["fail"])) {
    $fail = $_GET["fail"];
    if ($fail == "file") {
        if (isset($_GET["format"])) {
            $format = $_GET["format"];
            $msg_error = "Sorry. You have selected an unsupported format for " . $format . ".";
        }
    }
}
echo "branch: fail=file&format=\n";
echo "  rendered   = " . $msg_error . "\n\n";

echo "index.php line 191 does:  <?php echo \$msg_error;?>   -- no htmlspecialchars.\n";
