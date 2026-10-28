<?php
require_once './security.php';
csrf_auto_inject();

//GDIID 2025
require_once './Dbcontroller.php';
require_once './Util.php';
require_once './Notify.php';
require_once './Theme.php';

// Check if the theme is being set via toggle
if (isset($_POST['theme'])) {
    // Set the theme in the session based on the user selection
	Theme::setBodyTheme($_POST['theme']);
}

// Get the initial body theme
$theme = Theme::getBodyTheme();
$db_handle = new Dbcontroller();
$util = new Util();

if (isset($_SESSION["user"])) {

	$util->redirect("./admin/dashboard.php");
}

/*
 * FIX M3 in index/secure — the original read $_SESSION["project"] before
 * testing it, which is what wrote "Undefined array key project" into the
 * error log on every logged-out visit to secure.php.
 */
require_project();

/*
secure version 2: cleanup all notification. masukkan dalam Notify.php
*/

//fetch semua data berkaitan dengan project
if (isset($_SESSION["project"])) {
	$project_id  = (string)$_SESSION['project'];

	//update 2025 - unicode
    $db_handle->setCharset("utf8mb4");

    // Bound parameter instead of interpolated string.
	$project = db_one($db_handle->link(), "SELECT * FROM project WHERE id = ?", [$project_id]);

	if ($project === null) {
		// Stale session pointing at a deleted project.
		$_SESSION["project"] = null;
		unset($_SESSION["project"]);
		$util->redirect("./index.php");
	}

	/*
	 * FIX C4 — record what this session owns so download.php can prove
	 * ownership without trusting anything the browser sends.
	 */
	$_SESSION["project_email"] = (string)($project["email"] ?? '');
	$_SESSION["upload_id"]     = (string)($project["upload_id"] ?? '');

	$project_title = $project["title"];
	$registration_date = $project["reg_datetime"];
	$project_email = $project["email"];
	$project_organisation = $project["organisation"];
	$project_country = $project["country"];
	$project_contact = $project["contact_no"];
	$project_category = $project["cat_iid"];
	$status = $project["status"];
	$student_status = $project["student_status"];
	$payment_status = $project["payment_status"];
	$presentation_status = $project["presentation_status"] ?? "Pending";
	$abstract_status = $project["abstract_status"] ?? "Pending";
	$project_uitctkt = $project["uitmctkt"];
	$project_fees = $project["fees"] ?? null;
	$project_student_card = $project["student_card_link"];
	$project_abstract = $project["abstract_link"];
	$project_video = $project["video_link"] ?? null;
	$project_vid_yt = $project["vid_yt_link"] ?? null;
	$project_ex_abstract = $project["ex_abstract_file"] ;
	$project_turnitin = $project["turnitin_file"] ?? null;
	$project_currency = $project["currency"] ?? null;
	$project_last_signin = $project["last_signin"] ?? null;
	$project_current_signin = $project["current_signin"] ?? null;

	//data retrieve for project timeline
	$timeline = db_one($db_handle->link(), "SELECT * FROM project_timeline WHERE project_id = ?", [$project_id]);

	$register_timeline = $timeline["register"] ?? null;
	$payment_timeline = $timeline["payment"] ?? null;
	$payment_verified_timeline = $timeline["payment_verified"] ?? null;
	$presentation_timeline = $timeline["presentation"] ?? null;
	$presentation_verified_timeline = $timeline["presentation_verified"] ?? null;
	$ex_abstract_timeline = $timeline["ex_abstract"] ?? null;
	$ex_abstract_verified_timeline = $timeline["ex_abstract_verified"] ?? null;

	//project LOA — only the latest
	$loa = db_one($db_handle->link(),
		"SELECT file FROM project_loa WHERE project_id = ? AND email = ? ORDER BY id DESC LIMIT 1",
		[$project_id, $project_email]
	);
	$loa_file_name = $loa["file"] ?? null;
	$loa_file = $loa_file_name ? "./download-loa.php?file=" . urlencode($loa_file_name) : null;
}




?>

<!DOCTYPE html>
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

	<!-- CSS for Centering and Border -->
	<style>
		.qr-container {
			display: flex;
			justify-content: center;
			align-items: center;
			width: 170px; /* Adjust slightly bigger than QR */
			height: 170px;
			border: 2px solid #000; /* Add border */
			border-radius: 10px; /* Optional rounded border */
			margin: 0 auto; /* Center inside card */
			padding: 10px;
			background: #fff; /* Background color */
		}
	</style>
</head>
<body class="hold-transition layout-top-nav <?php echo $theme;?>">

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
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
							<div class="form-group">
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input" id="theme-toggle" 
									<?php echo $theme == 'dark-mode' ? 'checked' : ''; ?>>
									<label class="custom-control-label" for="theme-toggle">
										<?php if ($theme == 'dark-mode'): ?>
											<i class="fas fa-moon"></i> <!-- Sun icon for light mode -->
										<?php else: ?>
											<i class="fas fa-sun"></i> <!-- Moon icon for dark mode -->
										<?php endif; ?>
									</label>
								</div>
							</div>
						</li>
						<li class="nav-item">
							<a href="#" class="nav-link">
								<i class="fas fa-user"></i>
								<?php echo e($project_email); ?>
							</a>
						</li>
						<li class="nav-item">
							<a href="./logout.php" class="nav-link">
								<i class="fas fa-sign-out-alt"></i>
								Log out
							</a>
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
					?>
					<div class="row">

						<!-- all content will be here -->

						<div class="col-lg-6">
							<div class="card card-primary">
								<div class="card-header">
									<h3 class="card-title"><b>Registration Progress</b></h3>
									<div class="card-tools">
										<!-- Collapse Button -->
										<button type="button" class="btn btn-tool" data-card-widget="collapse">
											<i class="fas fa-minus"></i>
										</button>
									</div>
								</div>
								<div class="card-body">
									<div class="timeline">
										<!-- Register -->
										<div>
											<i class="fas fa-file-signature <?php echo ($status == 'REGISTER' || $student_status == 'VERIFIED' || $payment_status == 'PAID' || $presentation_status == 'VERIFIED' || $abstract_status == 'VERIFIED') ? 'bg-success' : 'bg-secondary'; ?>"></i>
											<div class="timeline-item">
												<?php if ($register_timeline != NULL) {
													echo '<span class="time"><i class="fas fa-clock"></i> '.$register_timeline.'</span>';
												}?>
												<h3 class="timeline-header">Registration</h3>
												<div class="timeline-body">
													Your registration has been recorded. Please complete the payment before submitting your video presentation. 
												</div>
											</div>
										</div>

										<!-- Student Card Verification -->
										<div>
											<i class="fas fa-id-card <?php echo ($student_status == 'VERIFIED' || $payment_status == 'PAID' || $presentation_status == 'VERIFIED' || $abstract_status == 'VERIFIED') ? 'bg-success' : 'bg-secondary'; ?>"></i>
											<div class="timeline-item">
												<?php if ($register_timeline != NULL) {
													echo '<span class="time"><i class="fas fa-clock"></i> '.$register_timeline.'</span>';
												}?>
												<h3 class="timeline-header">Student Verification</h3>
												<div class="timeline-body">
													Student status has been verified succesfully.
												</div>
											</div>
										</div>

										<div>
											<i class="fas fa-newspaper <?php echo ($student_status == 'VERIFIED' || $payment_status == 'PAID' || $presentation_status == 'VERIFIED' || $abstract_status == 'VERIFIED') ? 'bg-success' : 'bg-secondary'; ?>"></i>
											<div class="timeline-item">
												<?php if ($register_timeline != NULL) {
													echo '<span class="time"><i class="fas fa-clock"></i> '.$register_timeline.'</span>';
												}?>
												<h3 class="timeline-header">Abstract Verification</h3>
												<div class="timeline-body">
													Abstract submission has been verified succesfully.
												</div>
											</div>
										</div>

										<!-- Payment -->
										<div>
											<i class="fas fa-dollar-sign <?php echo ($payment_status == 'PAID' || $presentation_status == 'VERIFIED' || $abstract_status == 'VERIFIED') ? 'bg-success' : 'bg-secondary'; ?>"></i>
											<div class="timeline-item">
												<?php if ($payment_verified_timeline != NULL) {
													echo '<span class="time"><i class="fas fa-clock"></i> '.$payment_verified_timeline.'</span>';
												}?>
												<h3 class="timeline-header">Payment Verification</h3>
												<div class="timeline-body">
													<?php
													if ($payment_status == 'PAID') {
														echo "Your payment has been verified successfully.";
													}else{
														echo "Kindly complete your payment.";
													}
													?>
												</div>
											</div>
										</div>

										<!-- Presentation -->
										<div>
											<i class="fas fa-tv <?php echo ($presentation_status == 'ACCEPT' || $abstract_status == 'COMPLETE') ? 'bg-success' : 'bg-secondary'; ?>"></i>
											<div class="timeline-item">
												<?php if ($presentation_verified_timeline != NULL) {
													echo '<span class="time"><i class="fas fa-clock"></i> '.$presentation_verified_timeline.'</span>';
												}?>
												<h3 class="timeline-header">Presentation Verification</h3>
												<div class="timeline-body">
													<?php
													if ($presentation_status == 'ACCEPT') {
														echo "Your video presentation has been verified successfully. Kindly share and like your YouTube video.";
													}else{
														echo "Kindly submit your video presentation.";
													}
													?>
												</div>
											</div>
										</div>

										<!-- Extended Abstract -->
										<div>
											<i class="fas fa-file-word <?php echo ($abstract_status == 'COMPLETE') ? 'bg-success' : 'bg-secondary'; ?>"></i>
											<div class="timeline-item">
												<?php if ($ex_abstract_verified_timeline != NULL) {
													echo '<span class="time"><i class="fas fa-clock"></i> '.$ex_abstract_verified_timeline.'</span>';
												}?>
												<h3 class="timeline-header">Extended Abstract Verification (Optional)</h3>
												<div class="timeline-body">
													<?php
													if ($abstract_status == 'COMPLETE') {
														echo "Your extended abstract has been verified successfully.";
													}else if($abstract_status == 'NA'){
														echo "Kindly submit your extended abstract.";
													}else{
														echo "Your extended abstract is under verification.";
													}
													?>
												</div>
											</div>
										</div>
										<div>
											<i class="fas fa-trophy bg-primary"></i>
											<!-- <img src="./dist/img/logo_2025.jpg" alt="Completion Badge" class="circle-image"> -->
										</div>
									</div>
								</div>
							</div>


						</div>

						<!-- project details. gather from database -->
						<div class="col-lg-6 col-md-6">
							<div class="card card-primary">
								<div class="card-header text-center">
									<h3 class="card-title"><b>Project Details</b></h3>
									<div class="card-tools">
										<!-- Collapse Button -->
										<button type="button" class="btn btn-tool" data-card-widget="collapse">
											<i class="fas fa-minus"></i>
										</button>
									</div>
								</div>
								<div class="card-body">
									<table class="table table-bordered">
										<tbody>
											<tr>
												<th>Project Title</th>
												<td><?php echo e($project_title); ?></td>
											</tr>
											<tr>
												<th>Project ID</th>
												<td><?php echo $project_id; ?></td>
											</tr>
											<tr>
												<th>Registration Date</th>
												<td><?php echo $registration_date; ?> <small><b>GMT+8</b></small></td>
											</tr>
											<tr>
												<th>Category</th>
												<td><?php
												//if A Science And Technology
												//if B Social Science
												if ($project_category == "A") {
													echo "Science and Technology";
												}else{
													echo "Social Science";
												}
											?></td>
										</tr>
										<tr>
											<th>Last Sign-In</th>
											<td><?php echo $project_last_signin; ?> <small><b>GMT+8</b></small></td>
										</tr>
										<tr>
											<th>Current Sign-In</th>
											<td><?php echo $project_current_signin; ?> <small><b>GMT+8</b></small></td>
										</tr>
										<tr>
											<th>Organisation</th>
											<td><?php echo e($project_organisation); ?></td>
										</tr>
										<tr>
											<th>Country</th>
											<td><?php echo e($project_country); ?></td>
										</tr>
										<tr>
											<th>Document(s)</th>
											<td>											
												<a class="btn btn-info btn-block" target="_blank" href="<?php echo $loa_file; ?>">Letter of Acceptance (LOA)</a>
												

											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="col-lg-6 col-md-6">
						<div class="card card-blue">
							<div class="card-header with-border">
								<h3 class="card-title"><b>Step 1:</b>Fees and Payment</h3>
							</div>
							<div class="card-body">
								<div class="info-box bg-gradient-indigo">
									<span class="info-box-icon"><i class="fa fa-dollar-sign"></i></span>

									<div class="info-box-content">
										<span class="info-box-text">Payment Status</span>
										<span class="info-box-number"><?php echo $payment_status; ?></span>

										<div class="progress">
											<?php
												/*
												the progress will be:
												0% unpaid
												50% uploaded
												10% rejected
												100% verified
												*/
												if ($payment_status == "UNPAID") {
													$payment_progress = 0;
												}
												else if ($payment_status == "UPLOAD") {
													$payment_progress = 50;
												}
												else if ($payment_status == "PAID") {
													$payment_progress = 100;
												}
												else if ($payment_status == "REMARK") {
													$payment_progress = 30;
												}else{
													$payment_progress = 20;
												}
												?>
												<div class="progress-bar" style="width: <?php echo $payment_progress;?>%"></div>
											</div>
											<span class="progress-description">
												<?php

												if ($payment_status == "UNPAID") {
													$payment_remark = "Please make your payment and upload the proof of payment here";
												}
												else if ($payment_status == "UPLOAD") {
													$payment_remark = "Your proof of payment is under verification.";
												}
												else if ($payment_status == "PAID") {
													$payment_remark = "Your payment has been received successfully.";
												}
												else if ($payment_status == "REMARK") {
													$payment_remark = "Certain issues have been identified.";
												}else{
													$payment_remark = "Not Available";
												}
												echo $payment_remark;
												?>
												
											</span>
										</div>
										<!-- /.info-box-content -->
									</div>
									<?php
									if ($payment_status == "UNPAID" || $payment_status == "REMARK") {
										?>

										<form action="payment.php" method="POST" enctype="multipart/form-data">
											<small class="form-text text-muted mb-2">Please click the button below to proceed with your payment and upload the receipt.</small>

											<?php
										//baca table fees untuk retreve fees and link dia.
											if ($project_uitctkt) {
												$project_fee_category = "UITMCTKT";
											}else{
												if ($project_country == "Malaysia") {
													$project_fee_category = "MALAYSIAN";
												}else{
													$project_fee_category = "INTERNATIONAL";
												}
											}

											$sql_payment_rate = "SELECT * FROM payment_rate WHERE category = '$project_fee_category'";
											$result_payment_rate = $db_handle->run($sql_payment_rate);
											while ($payment_rate = mysqli_fetch_assoc($result_payment_rate)) {
											//get validity of the rate
												$payment_id = $payment_rate["id"];
												$payment_name = $payment_rate["name"];
												$payment_category = $payment_rate["category"];
												$payment_date_end = $payment_rate["date_end"];
												$p_rate = $payment_rate["rate"];
												$payment_link = $payment_rate["link"];
											//check date dah ke belum. kalau dah skip
												date_default_timezone_set('Asia/Kuala_Lumpur');
												$currentDate = date("Y-m-d");
												$date1 = new DateTime($payment_date_end);
												$date2 = new DateTime($currentDate);
												if ($date1 < $date2) {
												continue; //tarikh dah lepas
											}

											
											?>
											<div class="qr-container" id="qrcode"></div>

											<script>
												var qrcode = new QRCode(document.getElementById("qrcode"), {
													text: "<?php echo $payment_link?>",
													width: 150,
													height: 150
												});
											</script>
											<a target="_blank" class="btn btn-primary btn-block mt-3 mb-3" href="<?php echo $payment_link;?>">Make Payment</a>
											<?php
										}
										?>										
										<label for="payment_date" class="form-label">Date of payment</label>
										<div class="input-group">                                            
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-calendar"></i></span>
											</div>
											<input type="date" id="payment_date" class="form-control" placeholder="Date of payment" name="payment_date" required="" />
										</div>
										<label for="upload_payment" class="form-label">Proof of Payment</label>											
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-upload"></i></span>
											</div>
											<input type="file" id="upload_payment" class="form-control" placeholder="Payment Slip" accept=".png, .jpg, .jpeg, .heic, .pdf" name="payment_slip" required="" />
										</div>
										<small class="form-text text-muted">only accept .PNG/.JPEG/.PDF file</small>
										<small class="form-text text-muted mb-2">Refer to this <a target="_blank" href="https://gdiid.uitm.edu.my/#payment">guideline</a></small>
										<div class="form-group">
											<input type="submit" class="btn btn-primary float-right mb-2" name="submit_payment" value="Submit" />
										</div>
										<input type="hidden" name="email" value="<?php echo e($project_email);?>">
										<input type="hidden" name="payment_method" value="<?php echo $payment_id;?>">
									</form>
									<!-- </div> -->
									<?php
									}//end of payment_status=NA
									?>
									<?php
									if ($payment_status == "UPLOAD" || $payment_status == "PAID") {
										//display the last uploaded proof of payment details
										$sql_upload_payment = "SELECT * FROM project_payment WHERE project_id = '$project_id'  ORDER BY id DESC LIMIT 1 ";
										$result_uplaod_payment = $db_handle->run($sql_upload_payment);
										while ($row_upload_payment = mysqli_fetch_assoc($result_uplaod_payment)) {
											$payment_date = $row_upload_payment["payment_date"];
											$payment_proof = $row_upload_payment["payment_proof"];
											$payment_type = $row_upload_payment["payment_type"];
											$payment_recorded = $row_upload_payment["timestamp"];
											$verified_timestamp = $row_upload_payment["verification_timestamp"];
										}
										?>
										<div class="table-responsive">
											<table class="table table-bordered">
												<tbody>
													<tr>
														<th style="width: 30%;">Payment Date</th>
														<td><?php echo htmlspecialchars($payment_date); ?></td>
													</tr>
													<tr>
														<th>Received On</th>
														<td><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($payment_recorded); ?><small><b>GMT+8</b></small></td>
													</tr>
													<?php
													if ($verified_timestamp != NULL || $verified_timestamp != '') {
														?>
														<tr>
															<th>Verfied On</th>
															<td><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($verified_timestamp); ?><small><b>GMT+8</b></small></td>
														</tr>
														<?php
													}
													?>
													<tr>
														<th>Payment Proof</th>
														<td>
															<?php if (!empty($payment_proof)) { ?>
																<a href="./download-pop.php?file=<?php echo htmlspecialchars($payment_proof); ?>" target="_blank" class="btn btn-block btn-info">Download</a>
															<?php } else { ?>
																No proof uploaded
															<?php } ?>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
										<?php
									}//end of payment_status UPLOAD OR ACCEPT
									?>
									<?php
									//display this message after the input field
									if ($payment_status == "REMARK") {
										//display the last uploaded proof of payment details
										$sql_upload_payment = "SELECT * FROM project_payment WHERE project_id = '$project_id'  ORDER BY id DESC LIMIT 1 ";
										$result_uplaod_payment = $db_handle->run($sql_upload_payment);
										while ($row_upload_payment = mysqli_fetch_assoc($result_uplaod_payment)) {
											$payment_date = $row_upload_payment["payment_date"];
											$payment_proof = $row_upload_payment["payment_proof"];
											$payment_type = $row_upload_payment["payment_type"];
											$payment_recorded = $row_upload_payment["timestamp"];
											$reject_reason = $row_upload_payment["reject_reason"];
											$reject_comment = $row_upload_payment["reject_comment"];
											$verified_timestamp = $row_upload_payment["verification_timestamp"];
										}
										?>
										<div class="table-responsive">
											<table class="table table-bordered">
												<tbody>
													<tr>
														<th style="width: 30%;">Payment Date</th>
														<td><?php echo htmlspecialchars($payment_date); ?></td>
													</tr>
													<tr>
														<th>Recorded On</th>
														<td><?php echo htmlspecialchars($payment_recorded); ?> <small><b>GMT+8</b></small></td>
													</tr>
													<?php if (!empty($verified_timestamp)) { ?>
														<tr>
															<th>Verified On</th>
															<td><?php echo htmlspecialchars($verified_timestamp); ?> <small><b>GMT+8</b></small></td>
														</tr>
													<?php } ?>
													<tr class="table-danger">
														<th>Rejection Reason</th>
														<td><?php echo !empty($reject_reason) ? htmlspecialchars($reject_reason) : 'N/A'; ?></td>
													</tr>
													<tr class="table-danger">
														<th>Rejection Comment</th>
														<td><?php echo !empty($reject_comment) ? nl2br(htmlspecialchars($reject_comment)) : 'No comments provided'; ?></td>
													</tr>
													<tr>
														<th>Payment Proof</th>
														<td>
															<?php if (!empty($payment_proof)) { ?>
																<a href="./download-pop.php?file=<?php echo htmlspecialchars($payment_proof); ?>" target="_blank" class="btn btn-block btn-info">
																	<i class="fa fa-download"></i> Download Proof
																</a>
															<?php } else { ?>
																No proof uploaded
															<?php } ?>
														</td>
													</tr>
												</tbody>
											</table>
										</div>

										<?php
									} //end of payment status REJECT
									?>

								</div>
								<!-- /.card-body -->
							</div>
							<!-- .card .card-indigo -->
						</div>
						<!-- .col-lg-6 .col-md-6 -->

						<?php
						if ($payment_status == "PAID") {
							include 's_presentation.php';
							include 's_extended_abstract.php';

						}else{
							include 's_no_presentation.php';
						}
						?>

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
	<!-- qrcode generator -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
	<!-- Theme darkmode-->
	<!-- Auto logout -->
	<script src="./dist/js/auto.js"></script>
	<script>
		 // Get today's date in YYYY-MM-DD format
		var today = new Date().toISOString().split('T')[0];

    	// Set the max attribute to prevent future dates
		document.getElementById("payment_date").setAttribute("max", today);

		document.getElementById("upload_payment").addEventListener("change", function () {
			var file = this.files[0];
			if (file) {
				var allowedExtensions = ["png", "jpg", "jpeg", "heic", "pdf"];
				var fileExtension = file.name.split('.').pop().toLowerCase();

				if (!allowedExtensions.includes(fileExtension)) {
					alert("Invalid file type! Only PNG, JPG, HEIC, and PDF files are allowed.");
                this.value = ""; // Reset file input
            }
        }
    });


        // Function to toggle dark mode/light mode based on the switch
		const themeToggle = document.getElementById('theme-toggle');
		themeToggle.addEventListener('change', function() {
			const newTheme = themeToggle.checked ? 'dark-mode' : 'light-mode';

            // Send the selected theme to the server (via POST) to persist across reloads
			const formData = new FormData();
			formData.append('theme', newTheme);

			fetch(window.location.href, {
				method: 'POST',
				body: formData
			}).then(response => {
                // Reload the page to apply the new theme
				location.reload();
			});
		});
	</script>
</body>
</html>
