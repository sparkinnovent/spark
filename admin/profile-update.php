<?php

session_start();
require_once __DIR__ . '/../security.php';

// Admin console: refuse to render without an authenticated administrator.
require_admin();
require_once '../Dbcontroller.php';
require_once '../Util.php';
require_once './Usersession.php';
require_once '../Notify.php';

$db_handle = new DBController();
$util = new Util();
$user = new Usersession();

//***** get the user data from db using session
if (isset($_GET['id'])) {
 	$user_id = $_GET['id'];
 }else{
 	$user_id = $user->getID();
 }

 $sql = "SELECT * FROM members WHERE member_id=?";
 $params = [$user_id];
 $result = $db_handle->prepareAndExecute($sql, $params);
 $row_user = $result->fetch_assoc();
?>

<!DOCTYPE HTML>

<html lang="en" style="height: auto;" class=""><head>

	<meta charset="utf-8">

	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>GDIID ADMIN | Dashboard</title>



	<!-- Google Font: Source Sans Pro -->

	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&amp;display=fallback">

	<!-- Font Awesome Icons -->

	<link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">

	<!-- overlayScrollbars -->

	<link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">

	<!-- Theme style -->

	<link rel="stylesheet" href="../dist/css/adminlte.min.css?v=3.2.0">

	<!-- DataTables -->

	<link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

	<link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">

	<link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

</head>

<body class="layout-fixed layout-navbar-fixed layout-footer-fixed control-sidebar-slide-open sidebar-mini" style="height: auto;">

	<div class="wrapper">

		<!-- Preloader -->

		<div class="preloader flex-column justify-content-center align-items-center" style="height: 100vh; display: flex;">

			<img class="animation__wobble" src="../dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">

		</div>



		<?php 

		include './v2-navbar.php';



		include './v2-sidebar.php';

		?>



<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper" style="min-height: 716px;">

	<!-- Content Header (Page header) -->

	<div class="content-header">

		<div class="container-fluid">

			<div class="row mb-2">

				<div class="col-sm-6">

					<h1 class="m-0">GDIID - Update Profile</h1>

				</div><!-- /.col -->

				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">

					<ol class="breadcrumb float-sm-right">

						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>

						<li class="breadcrumb-item active">Update Profile</li>

					</ol>

				</div><!-- /.col -->

			</div><!-- /.row -->

		</div><!-- /.container-fluid -->

	</div>

	<!-- /.content-header -->



	<!-- Main content -->

	<section class="content">

		<div class="container-fluid">
			<?php
			Notify::display();
			?>

			<div class="row">

				<div class="col-md-12">

					<div class="card card-primary">

						<div class="card-header">

							<h3 class="card-title">Update Profile</h3>

						</div>

						<!-- /.card-header -->

						<!-- form start -->

						<form method="POST" action="member.process.php" enctype="multipart/form-data">

							<div class="card-body">

								<div class="text-center mb-4">

									<img src="./dist/<?php echo $row_user["photo"];?>" alt="Profile Photo" id="photo-preview" class="img-thumbnail" style="max-width: 150px;">

								</div>

								<div class="row">

									<!-- Username -->

									<div class="col-md-6">

										<div class="form-group">

											<label for="username">Username</label>

											<input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" value="<?php echo $row_user["member_name"];?>" required>

										</div>

									</div>



									<!-- Email -->

									<div class="col-md-6">

										<div class="form-group">

											<label for="email">Email</label>

											<input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo $row_user["member_email"];?>" required>

										</div>

									</div>

								</div>



								<div class="row">

									<!-- Password -->

									<div class="col-md-6">

										<div class="form-group">

											<label for="password">Password</label>

											<input type="password" class="form-control" id="password" name="password" placeholder="Enter a new password. Keep blank if you don't want to update.">

										</div>

									</div>



									<!-- Re-enter Password -->

									<div class="col-md-6">

										<div class="form-group">

											<label for="repassword">Re-enter Password</label>

											<input type="password" class="form-control" id="repassword" name="repassword" placeholder="Re-enter the new password. Keep blank if you don't want to update.">

										</div>

									</div>

								</div>



								<div class="row">

									<!-- Profile Photo -->

									<div class="col-md-6">

										<div class="form-group">

											<label for="profile_photo">Profile Photo</label>

											<div class="custom-file">

												<input type="file" class="custom-file-input" id="profile_photo" name="profile_photo">

												<label class="custom-file-label" for="profile_photo">Choose file</label>

											</div>											

										</div>

									</div>



									<!-- Role -->

									<div class="col-md-6">

										<div class="form-group">

											<label for="role">Role</label>

											<select class="form-control" id="role" name="role" required>

												<option value="">Select Role</option>

												<option value="1" <?php if ($row_user["member_level"] == "1"): ?>
													selected
												<?php endif ?>>Super Admin</option>

												<option value="2" <?php if ($row_user["member_level"] == "2"): ?>
													selected
												<?php endif ?>>Admin</option>

												<option value="3" <?php if ($row_user["member_level"] == "3"): ?>
													selected
												<?php endif ?>>Other</option>

											</select>

										</div>

									</div>

								</div>

							</div>

							<!-- /.card-body -->



							<div class="card-footer text-right">
								<?php $currentPath = basename($_SERVER['PHP_SELF']);?>
								
								<input type="hidden" name="origin" value="<?php echo $currentPath;?>">

								<button type="submit" class="btn btn-primary" name="update_profile">Update Profile</button>

								<button type="reset" class="btn btn-secondary">Reset</button>

							</div>

						</form>

					</div>

<!-- /.card -->



</div>

<!-- /.col -->

</div>

<!-- /.row -->



<div class="row">

	<div class="col-md-12">

	</div>

	<!-- /.col -->

</div>

<!-- /.row -->

</div><!--/. container-fluid -->

</section>

<!-- /.content -->

</div>

<!-- /.content-wrapper -->



<!-- Control Sidebar -->





<!-- Main Footer -->

<footer class="main-footer">

	<strong>Copyright © 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong>

	All rights reserved.

	<div class="float-right d-none d-sm-inline-block">

		<b>Version</b> 3.2.0

	</div>

</footer>

<div id="sidebar-overlay"></div></div>

<!-- ./wrapper -->



<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->

<script src="../plugins/jquery/jquery.min.js"></script>

<!-- Bootstrap -->

<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- overlayScrollbars -->

<script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>

<!-- AdminLTE App -->

<script src="../dist/js/adminlte.js?v=3.2.0"></script>

<!-- PAGE PLUGINS -->

<!-- jQuery Mapael -->

<script src="../plugins/jquery-mousewheel/jquery.mousewheel.js"></script>

<script src="../plugins/raphael/raphael.min.js"></script>

<script src="../plugins/jquery-mapael/jquery.mapael.min.js"></script>

<script src="../plugins/jquery-mapael/maps/usa_states.min.js"></script>

<!-- ChartJS -->

<script src="../plugins/chart.js/Chart.min.js"></script>

<!-- DataTables  & Plugins -->

<script src="../plugins/datatables/jquery.dataTables.min.js"></script>

<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>

<script src="../plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>

<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>

<script src="../plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>

<script src="../plugins/jszip/jszip.min.js"></script>

<script src="../plugins/pdfmake/pdfmake.min.js"></script>

<script src="../plugins/pdfmake/vfs_fonts.js"></script>

<script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>

<script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>

<script src="../plugins/datatables-buttons/js/buttons.colVis.min.js"></script>



<script type="text/javascript">

	document.addEventListener("DOMContentLoaded", function () {

    // Simulate preloader timeout (e.g., remove preloader after 1.5 seconds)

		setTimeout(function () {

			document.querySelector(".preloader").style.display = "none";

    }, 1500); // 1500ms = 1.5 seconds

	});



</script>



<script type="text/javascript">

	$(document).ready(function () {

		var table1 = $("#example1").DataTable({

			"responsive": true, "lengthChange": false, "autoWidth": false,

			"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

		});



		table1.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');



		var table2 = $("#example2").DataTable({

			"responsive": true, "lengthChange": false, "autoWidth": false,

			"buttons" : [

				{ extend: 'csv', className: 'btn btn-success' },

				{ extend: 'excel', className: 'btn btn-success' },

				{ extend: 'pdf', className: 'btn btn-success' }

			]

		});



		table2.buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');



		var table3 = $("#example3").DataTable({

			"responsive": true, "lengthChange": false, "autoWidth": false,

			'columnDefs' : [

        //hide the second & fourth column

				{ 'visible': false, 'targets': [4,5,6] }

			],

			"buttons" : [

				{ extend: 'copy', className: 'btn btn-success' },

				{ extend: 'csv', className: 'btn btn-success' },

				{ extend: 'excel', className: 'btn btn-success' },

				{ extend: 'pdf', className: 'btn btn-success' },

				{ extend: 'print', className: 'btn btn-success' },

				{ extend: 'colvis', className: 'btn btn-success' }

			]

		});





		table3.buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');



		var table4 = $("#example4").DataTable({

			"responsive": true, "lengthChange": false, "autoWidth": false,

			"buttons" : [

				{ extend: 'copy', className: 'btn btn-success' },

				{ extend: 'csv', className: 'btn btn-success' },

				{ extend: 'excel', className: 'btn btn-success' },

				{ extend: 'pdf', className: 'btn btn-success' },

				{ extend: 'print', className: 'btn btn-success' },

				{ extend: 'colvis', className: 'btn btn-success' }

			]

		});

	});



	

</script>



</body>

</html>