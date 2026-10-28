<?php
session_start();
require_once '../security.php';
require_once '../Notify.php';

/*
 * FIX H1 — $err_msg / $ok_msg come from ?fail= and ?success= and were echoed
 * raw into the alert div. Confirmed reflected XSS. Both are now escaped.
 * Login POSTs also carry a CSRF token.
 */
$err_msg = isset($_GET["fail"])    ? (string)$_GET["fail"]    : null;
$ok_msg  = isset($_GET["success"]) ? (string)$_GET["success"] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GDDID | Log in</title>

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
    <div class="login-logo">
      <a href="../index.php"><b>GDIID</b>ADMIN</a>
    </div>
    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">
        <p class="login-box-msg">Sign in to start your session</p>

        <?php
        Notify::display();
        if (isset($err_msg)) {
          ?>
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> Alert!</h5>
            <?php echo e($err_msg); ?>
          </div>
          <?php
        }
        ?>
        <?php
        if (isset($ok_msg)) {
          ?>
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> Alert!</h5>
            <?php echo e($ok_msg); ?>
          </div>
          <?php
        }
        ?>

        <form action="./login.process.php" method="post">
          <?php echo csrf_field(); ?>
          <div class="input-group mb-3">
            <input type="email" class="form-control" placeholder="Email" name="member_email" required="">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>
          <div class="input-group mb-3">
            <input type="password" class="form-control" placeholder="Password" id="password" name="member_password" required="">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
                <span class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></span>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-8">
              <div class="icheck-primary">
                <input type="checkbox" id="remember" name="remember" <?php if(isset($_COOKIE["member_login"])) { ?> checked <?php } ?>>
                <label for="remember">
                  Remember Me
                </label>
              </div>
            </div>
            <!-- /.col -->
            <div class="col-4">
              <input type="submit" class="btn btn-primary btn-block" name="login" value="Sign In" />
            </div>
            <!-- /.col -->
          </div>
        </form>

        <p class="mb-1">
          <a href="forget.php">I forgot my password</a>
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
  <script type="text/javascript">
    function togglePassword(fieldId, eyeIcon) {
        var field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text"; // Show password
            eyeIcon.classList.remove("fa-eye");
            eyeIcon.classList.add("fa-eye-slash"); // Change icon to eye-slash
        } else {
            field.type = "password"; // Hide password
            eyeIcon.classList.remove("fa-eye-slash");
            eyeIcon.classList.add("fa-eye"); // Change icon to eye
        }
    }
  </script>
  <style>
    .toggle-password {
        cursor: pointer;
        margin-left: 10px;
    }
</style>
</body>
</html>
