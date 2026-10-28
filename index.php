<?php
session_start();
require_once __DIR__ . '/security.php';
//GDIID 2025

// Fresh install? Send the administrator to the installer.
if (!is_file(__DIR__ . '/config.php') && is_dir(__DIR__ . '/install') && !is_file(__DIR__ . '/install/install.lock')) {
    header('Location: install/index.php');
    exit;
}

//include_once './Dbcontroller.php';
require_once './Util.php';
require_once './Notify.php';

//$db_handle = new DBController();
$util = new Util();

if (isset($_SESSION["user"])) {

	$util->redirect("./admin/dashboard.php");
}

$msg_success = null;
$msg_error   = null;

//handle msg 
if(isset($_GET["success"])){

	$success = (string)$_GET["success"];

    //alert success send email notification and update registration's status to NOTIFY
	if($success == "notify"){
		$msg_success = "Email notification to the project leader success.";
	}
	if($success == "payment"){
		$msg_success = "Thank you! We have received the proof of payment. This document will undergo a verification process (1 - 3 working days). Then, we will notify you through email when the verification completed.";
	}
	if($success == "presentation"){
		$msg_success = "Thank you! We have received the presentation video file successfully. This link will undergo a verification process (1 - 3 working days). We will notify you through email when the verification is complete.";
	}
	if($success == "extended_abstract"){
		$msg_success = "Thank you! We have received the abstract file(s) successfully. These files will undergo a verification process (1 - 3 working days). Then, we will notify you through email when the verification completed. ";
	}
}

/*
 * FIX M3 — this was:
 *     if(isset($_GET["fail_connect"])){ $$msg_error = $_GET["fail"]; }
 * `$$msg_error` is a variable variable writing to a name derived from an
 * undefined variable, so the banner never rendered, and it read the wrong key.
 * A connect failure is now logged by DBController; the visitor gets a neutral
 * message and no server detail.
 */
if (isset($_GET["fail_connect"])) {
	$msg_error = "The service is temporarily unavailable. Please try again in a few minutes.";
}

if(isset($_GET["fail"])){

	$fail = (string)$_GET["fail"];

    //alert success send email notification and update registration's status to NOTIFY
	if($fail == "loa"){
		$msg_error = "Sorry. The project not found. Would you please make sure your Project ID and corresponding email address are correct?";
	}
	if($fail == "payment_file"){
		$msg_error = "Sorry. You have the wrong file. Would you please upload PDF or JPG, or PNG file only?";
	}
	if($fail == "upload"){
		$msg_error = "Sorry. Your upload was unsuccessful. please do not hesitate to <a href='mailto:secretariatgdiid@uitm.edu.my'>contact us</a>.";
	}
	if($fail == "payment"){
		$msg_error = "Your proof of payment was unsuccessful. You may have uploaded proof of payment previously, which is still under verification.";
	}
	if($fail == "project_not_found"){
		$msg_error = "The project was not found. The Project ID and corresponding email address was not matched. If you forget your corresponding email or Project ID, please do not hesitate to <a href='mailto:secretariatgdiid@uitm.edu.my'>contact us</a>.";
	}
	if($fail == "presentation"){
		$msg_error = "Sorry. Your presentation video was unsuccessful. Please try again or contact us through live chat or email.";
	}
	if($fail == "present_accepted"){
		$msg_error = "Sorry. Your submission failed. Your presentation link under verification. We will send email notification YouTube link for your reference.";
	}
	if($fail == "extended_abstract_file"){
		$msg_error = "Sorry. You have uploaded the wrong file. Please upload DOC/DOCX for extended abstract and PDF for Turnitin.";
	}
	if($fail == "extended_abstract"){
		$msg_error = "Sorry. Your extended abstract submission was unsuccessful. Would you mind trying again or contacting us through live chat or email?";
	}
	if($fail == "extended_abstract_accepted"){
		$msg_error = "Sorry. You have completed your extended abstract. Don't hesitate to contact us through live chat or email if you have any issues or problems.";
	}
	if($fail == "extended_abstract_verify"){
		$msg_error = "Sorry. Your extended abstract submission was unsuccessful because your extended abstract still under verification. Please get in touch with us through live chat or email if you have any issues or problems.";
	}

	if ($fail == "file") {
		/*
		 * FIX H1 — ?format= ?size= ?move= were interpolated into the message and
		 * the message was echoed raw, giving reflected XSS on the sign-in page.
		 * The values are constrained to a short safe charset here.
		 */
		if (isset($_GET["format"])) {
			$format = preg_replace('/[^A-Za-z0-9 +._-]/', '', (string)$_GET["format"]);
			$msg_error = "Sorry. You have selected an unsupported format for ". $format .". Would you mind checking and selecting supported files only.";
		}

		if (isset($_GET["size"])) {
			$size = preg_replace('/[^A-Za-z0-9 +._-]/', '', (string)$_GET["size"]);
			$msg_error = "Sorry. You have selected an unsupported file size for ". $size .". Would you please check and choose supported file size only?";
		}

		if (isset($_GET["move"])) {
			$move = preg_replace('/[^A-Za-z0-9 +._-]/', '', (string)$_GET["move"]);
			$msg_error = "Sorry. Your ". $move ." file was failed to be stored in the server. Don't hesitate to get in touch with us through live chat or email.";
		}
	}

	/*
	 * FIX H1 — this assigned raw $_GET["fail"] into $msg_error. Sessions expiring
	 * is an internal event; there is no reason to echo the query string back.
	 */
	if (isset($_GET["session_expired"])) {
		$msg_error = "Your session has expired. Please sign in again.";
	}
}
?>

<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GDIID 2025</title>

	<!-- Google Font: Source Sans Pro -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<!-- Font Awesome Icons -->
	<link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="./dist/css/adminlte.min.css">
	<link rel="stylesheet" href="./dist/css/custom.css">

	<link href="./dist/img/uitm-icon.png" rel="icon">
</head>
<body class="hold-transition layout-top-nav">

	<!-- Preloader -->
	<div class="preloader flex-column justify-content-center align-items-center">
		<img class="animation__shake" src="./dist/img/logo_2025.jpg" alt="AdminLTELogo" height="200">
	</div>

	<div class="wrapper">

		<!-- Navbar -->
		<nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
			<div class="container">
				<a href="#" class="navbar-brand">
					<img src="../dist/img/logo_2025.jpg" alt="GDIID21 Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
					<span class="brand-text font-weight-light">GDIID 2025</span>
				</a>

				<button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse order-3" id="navbarCollapse">
					<!-- Left navbar links -->
					<ul class="navbar-nav">
						<li class="nav-item">
							<a href="https://gdiid.uitm.edu.my" class="nav-link">Official Website</a>
						</li>
					</ul>

				</div>
			</div>
		</nav>
		<!-- /.navbar -->

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper">
			<!-- Content Header (Page header) -->
			<div class="content-header">
				<div class="container">
					<div class="row mb-2">
						<div class="col-sm-12">
							<h1 class="m-0">Registration <small>System 2.0</small></h1>
						</div><!-- /.col -->
					</div><!-- /.row -->
				</div><!-- /.container-fluid -->
			</div>
			<!-- /.content-header -->

			<!-- Main content -->
			<div class="content">
				<div class="container">
					<?php
					Notify::display();
					if (isset($msg_error)) {
						?>
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
							<h4><i class="icon fa fa-ban"></i> Failed!</h4>
							<?php
						/*
						 * FIX H1 — two of these messages intentionally contain a
						 * mailto anchor. Escape everything, then rebuild only that
						 * one tag from validated parts. Nothing from the input is
						 * ever emitted as markup.
						 */
						echo spark_safe_message((string)$msg_error);
						?>
						</div> 
						<?php
					}
					?>
					<div class="row">
						<div class="col-lg-6">
							<div class="card card-primary">
								<div class="card-header text-center">
									<h3 class="card-title"><b>Welcome to GDIID 2025</b></h3>
								</div>
								<form action="./project.process.php" method="post">
									<?php echo csrf_field(); ?>
									<div class="card-body">
										<!-- Project ID -->
										<div class="form-group">
											<label for="project-id" class="font-weight-bold">Project ID</label>
											<input type="text" id="project-id" class="form-control" placeholder="Enter your Project ID" name="id" required>
											<small class="form-text text-muted">Refer to your Letter Of Acceptance (LOA).</small>
										</div>

										<!-- Email -->
										<div class="form-group">
											<label for="email" class="font-weight-bold">Email</label>
											<input type="email" id="email" class="form-control" placeholder="Enter your email" name="email" required>
											<small class="form-text text-muted">Enter your corresponding email address.</small>
										</div>

										<!-- Google reCAPTCHA Widget -->
										<div class="form-group text-center">
											<div class="g-recaptcha" data-sitekey="6Ld89cYqAAAAAGLh5hpVnaUn061feTJmmiaeTMwP" data-callback="enableSubmit"></div>
										</div>

									</div>
									<!-- /.card-body -->
									<div class="card-footer text-right">
										<button type="reset" class="btn btn-danger btn-lg">Reset</button>
										<input type="submit" id="submit-btn" name="project_signin" class="btn text-white bg-indigo btn-lg" value="Sign In" disabled />
									</div>
									<!-- card footer -->
								</form>
							</div>
							<!-- card primary -->

						</div>
						<!-- /.col-md-6 -->
						<div class="col-md-3">
							<div class="card">
								<img src="./dist/img/logo_2025.jpg" class="card-img-top" alt="Card Image">
								<div class="card-body">
									<h5 class="card-title"><b>GDDID 2025</b></h5>
									<p class="card-text">Graduate Digital Invention, Innovation and Design (GDIID) 2025</p>

								</div>
							</div>
						</div>


					</div>
					<!-- /.row -->
				</div><!-- /.container-fluid -->
			</div>
			<!-- /.content -->
		</div>
		<!-- /.content-wrapper -->


		<!-- Main Footer -->
		<footer class="main-footer">
			<!-- To the right -->
			<div class="float-right d-none d-sm-inline">
				GDIID 2025
			</div>
			<!-- Default to the left -->
			<strong>Copyright &copy; 2025 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
		</footer>
	</div>
	<!-- ./wrapper -->

	<!-- REQUIRED SCRIPTS -->
	<!-- jQuery -->
	<script src="./plugins/jquery/jquery.min.js"></script>
	<!-- Bootstrap 4 -->
	<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<!-- AdminLTE App -->
	<script src="./dist/js/adminlte.min.js"></script>
	<!-- Google reCAPTCHA Script -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
    function enableSubmit() {
        document.getElementById("submit-btn").disabled = false;
    }
</script>
</body>
</html>
