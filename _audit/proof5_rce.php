<?php
// Prove the upload path in payment.php lets an attacker choose the SAVED EXTENSION.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Attacker uploads a file literally named "shell.php" but sends
// Content-Type: image/jpeg on the multipart part.
$_FILES = [
    'payment_slip' => [
        'name'     => 'shell.php',
        'type'     => 'image/jpeg',      // <-- client supplied, trusted by payment.php
        'tmp_name' => '/tmp/phpXXXX',
        'size'     => 512,
        'error'    => 0,
    ],
];

$allowed_types = array('application/pdf', 'image/jpeg', 'image/png');

echo "gate 1: in_array(\$type, \$allowed_types) ? ";
var_dump(in_array($_FILES["payment_slip"]["type"], $allowed_types));

$path = $_FILES['payment_slip']['name'];
$ext  = pathinfo($path, PATHINFO_EXTENSION);
echo "gate 2: extension taken from user filename   = '$ext'\n";
echo "gate 3: no extension whitelist at all        = (none in payment.php)\n\n";

$newfilename = round(microtime(true)) . '.' . $ext;
echo "resulting saved path = './upload//payment/' . '$newfilename'\n";
echo "  -> PHP will EXECUTE this file: " . (substr($newfilename, -4) === '.php' ? "YES" : "no") . "\n\n";

echo "Does any guard exist in upload/payment/ ?\n";
$h = __DIR__ . '/../upload/payment/.htaccess';
echo "  upload/payment/.htaccess exists: " . (is_file($h) ? "yes" : "NO") . "\n";
echo "  upload/.htaccess contents: " . trim(@file_get_contents(__DIR__ . '/../upload/.htaccess')) . "\n";
