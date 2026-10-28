<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
require_once '../security.php';
require_once '../Dbcontroller.php';
require_once '../Util.php';
require_once '../Notify.php';

$db_handle = new DBController();
//$user = new Usersession();
$util = new Util();

function sendNotify($message, $email){
	//Load phpmailer
	require '../vendor/autoload.php';
	$mail = new PHPMailer(true);                             
   try {
	        //Server settings
       $mail->isSMTP();
	   //$mail->SMTPDebug = 2;
       /*
        * FIX — this file hardcoded the mailbox host, username and password in
        * source. Anyone with a copy of the repo had working SMTP credentials.
        * Values now come from config.php; nothing sensitive lives in PHP files.
        */
       $mail->Host       = defined('SMTP_HOST') ? (string)SMTP_HOST : '';
       $mail->SMTPAuth   = true;
       $mail->Username   = defined('SMTP_USER') ? (string)SMTP_USER : '';
       $mail->Password   = defined('SMTP_PASS') ? (string)SMTP_PASS : '';
       $mail->SMTPOptions = array(
           'ssl' => array(
               'verify_peer' => true,
               'verify_peer_name' => true,
               'allow_self_signed' => false
           )
       );
       $mail->SMTPSecure = 'ssl';
       $mail->Port       = 465;

       $mail->setFrom($mail->Username, defined('SMTP_FROM_NAME') ? (string)SMTP_FROM_NAME : 'Conference Secretariat');

	        //Recipients
       $mail->addAddress($email);
	   $mail->addReplyTo($mail->Username);
	        //Content
       $mail->isHTML(true);                                  
       $mail->Subject = 'GDIID | User Password Reset';
       $mail->Body    = $message;

       $mail->send();
	        //echo "<script>alert('email sent!');</script>";

   } 
   catch (Exception $e) {
       $_SESSION['error'] = 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
	        //header('location: signup.php');
       echo $_SESSION['error'];
   }	


}

if(isset($_POST["submit"])){

	csrf_verify();

	$member_email = trim((string)($_POST["email"] ?? ''));

	if (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
		Notify::set("Please enter a valid email address.", "danger");
		$util->redirect("./forget.php");
	}

	// Bound parameter; no string interpolation.
	$row1 = db_one($db_handle->link(), "SELECT member_id, member_name FROM members WHERE member_email = ? LIMIT 1", [$member_email]);

	/*
	 * FIX — this always reported success, so an attacker could enumerate which
	 * addresses have accounts. It also used uniqid(), which is time-based and
	 * predictable, for a password reset token.
	 *
	 * The response is now identical either way. Only a real account receives mail.
	 */
	if ($row1 !== null) {

		$token = secure_token(32);

		$stored = $db_handle->prepareAndExecute(
			"UPDATE members SET reset_token = ?, reset_token_at = ? WHERE member_id = ?",
			[$token, now_my(), (int)$row1["member_id"]]
		);

		if ($stored) {
			$safeName = htmlspecialchars((string)$row1["member_name"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$resetUrl = (defined('APP_URL') ? rtrim((string)APP_URL, '/') : 'https://gdiid.uitmkt.com')
				. '/admin/reset_password.php?token=' . urlencode($token);

			$message = "<h2>GDIID | Reset Password</h2>"
				. "<p>username: " . $safeName . "</p>"
				. "<p>Your reset password link is here. It expires in 60 minutes.</p>"
				. "<a href='" . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . "'>reset password</a>";

			sendNotify($message, $member_email);
		} else {
			error_log('reset token could not be stored for member ' . (int)$row1["member_id"]);
		}
	}

	Notify::set("If that address has an account, a reset link is on its way.", "success");
	$util->redirect("./forget.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>GDIID | Forget Password</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">

</head>
<body class="hold-transition login-page">

    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h1"><b>Forget</b>Password</a>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Please enter email address for reset password. Reset password link will be sent to your registered email address.</p>
          <form action="" method="post">
            <div class="input-group mb-3">
              <input type="email" class="form-control" placeholder="Email" name="email">
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
              </div>
          </div>
      </div>
      <div class="row">
          <div class="col-12">
            <input type="submit" name="submit" class="btn btn-primary btn-block btn-flat" value="Reset password" />
        </div>
        <!-- /.col -->
    </div>
</form>
<p class="mt-3 mb-1">
    <a href="./login.php">Login</a>
</p>
</div>
<!-- /.login-card-body -->
</div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
</body>
</html>


