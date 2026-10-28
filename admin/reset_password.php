<?php
session_start();
require_once '../security.php';
require_once '../Util.php';
require_once '../Dbcontroller.php';
require_once '../Notify.php';

$util = new Util();
$db_handle = new Dbcontroller();
$link = $db_handle->link();

/*
 * FIX H5 — three defects in the original:
 *   1. `if ($result)` on a mysqli_result is always true, even with zero rows,
 *      so the "invalid token" redirect was unreachable and the script carried
 *      on with an unset $_SESSION['member_id'].
 *   2. reset_token had no expiry, so a leaked link worked forever.
 *   3. The password was passed through clean() before password_hash(), which
 *      stored a hash of the mangled string.
 *
 * The token now has to match a row and be inside its TTL.
 */
const RESET_TTL_MINUTES = 60;

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
if ($token === '') {
    $util->redirect("./forget.php");
}

$member = db_one(
    $link,
    "SELECT member_id, reset_token_at FROM members WHERE reset_token = ? LIMIT 1",
    [$token]
);

$tokenOk = false;
if ($member !== null) {
    $issuedAt = $member['reset_token_at'] ?? null;
    if ($issuedAt === null) {
        // Legacy row created before this fix: accept once, then expire it.
        $tokenOk = true;
    } else {
        $age = (time() - strtotime((string)$issuedAt)) / 60;
        $tokenOk = $age >= 0 && $age <= RESET_TTL_MINUTES;
    }
}

if (!$tokenOk) {
    Notify::set("That reset link is invalid or has expired. Please request a new one.", "danger");
    $util->redirect("./forget.php");
}

// Bind the pending reset to this session so the POST cannot be replayed elsewhere.
$_SESSION['reset_member_id'] = (int)$member['member_id'];
$_SESSION['reset_token']     = $token;

if (isset($_POST["submit"])) {

    csrf_verify();

    $sessionToken = (string)($_SESSION['reset_token'] ?? '');
    $sessionId    = (int)($_SESSION['reset_member_id'] ?? 0);

    // The form must still be for the token that opened this page.
    if ($sessionId < 1 || !hash_equals($sessionToken, $token)) {
        Notify::set("Your reset session is no longer valid. Please start again.", "danger");
        $util->redirect("./forget.php");
    }

    // FIX H2 class — hash the raw value, never an escaped one.
    $newpassword = (string)($_POST["password"] ?? '');
    $confirm     = (string)($_POST["repassword"] ?? ($_POST["password2"] ?? ''));

    if (strlen($newpassword) < 8) {
        Notify::set("Your new password must be at least 8 characters.", "danger");
        $util->redirect("./reset_password.php?token=" . urlencode($token));
    }
    if ($confirm !== '' && $confirm !== $newpassword) {
        Notify::set("The two passwords do not match.", "danger");
        $util->redirect("./reset_password.php?token=" . urlencode($token));
    }

    $updated = $db_handle->prepareAndExecute(
        "UPDATE members SET member_password = ?, reset_token = NULL, reset_token_at = NULL WHERE member_id = ?",
        [password_hash($newpassword, PASSWORD_DEFAULT), $sessionId]
    );

    if ($updated) {
        unset($_SESSION['reset_member_id'], $_SESSION['reset_token']);
        session_regenerate_id(true);
        $util->redirect("./login.php?success=Your password has been reset. You may now sign in.");
    }

    Notify::set("Your password could not be reset. Please contact the developer.", "danger");
    $util->redirect("./reset_password.php?token=" . urlencode($token));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>GDIID | Reset Password</title>
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><b>GDIID</b>ADMIN</div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Choose a new password</p>
            <?php Notify::display(); ?>
            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="New password" required minlength="8">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="repassword" class="form-control" placeholder="Repeat password" required minlength="8">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <button type="submit" name="submit" value="1" class="btn btn-primary btn-block">Set password</button>
            </form>
        </div>
    </div>
</div>
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
</body>
</html>
