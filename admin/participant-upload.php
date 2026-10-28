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
			<?php Notify::display();?>
			<div class="row mb-2">

				<div class="col-sm-6">

					<h1 class="m-0">GDIID - Upload Participant</h1>

				</div><!-- /.col -->

				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">

					<ol class="breadcrumb float-sm-right">

						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>

						<li class="breadcrumb-item"><a href="./participant.php">Participant</a></li>

						<li class="breadcrumb-item active">Upload Participant</li>

					</ol>

				</div><!-- /.col -->

			</div><!-- /.row -->

		</div><!-- /.container-fluid -->

	</div>

	<!-- /.content-header -->



	<!-- Main content -->

	<section class="content">

		<div class="container-fluid">

			<div class="row">

				<div class="col-12">

					<div class="card card-primary card-outline">

						<div class="card-header">

							<h5 class="card-title">Upload Participant</h5>

							<a class="btn btn-info float-right" target="_blank" href="download/GDIID 2025 _ REGISTRATION FORM (Responses).xlsx"><i class="fa fa-download"></i> Example</a>

						</div>

						<div class="card-body">

							<div class="alert alert-warning" role="alert">
								Please use only XLSX or XLS files and ensure they contain only new projects.
							</div>



							<div class="d-flex justify-content-between">

								<!-- Form 1 -->

								<form method="post" enctype="multipart/form-data" action="./upload_project.process.php" class="w-50 mr-3">

									<div class="form-group">

										<label>New Registration (public)</label>
										<div class="input-group mb-3">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-upload"></i></span>
											</div>
											<input type="file" class="form-control" name="file" />
											<div class="input-group-append">
												<input type="submit" class="btn btn-primary" name="import" value="Upload" />
											</div>
										</div>
									</div>
								</form>
								<!-- Form 2 -->
								<form method="post" enctype="multipart/form-data" action="./upload_project.process.php?cat=uitmkt" class="w-50">
									<div class="form-group">
										<label>New Registration Internal (UITMCTKKT)</label>
										<div class="input-group mb-3">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-upload"></i></span>
											</div>
											<input type="file" class="form-control" name="file" />
											<div class="input-group-append">
												<input type="submit" class="btn btn-primary" name="import" value="Upload" />
											</div>
										</div>
									</div>
								</form>
							</div>
						</div>
						<!-- card body -->
					</div>
					<!-- card -->
				</div>
				<!-- /.col -->
				<div class="col-md-12">


					<div class="card card-indigo card-outline">

						<div class="card-header">

							<h5 class="card-title m-0">Manage upload</h5>

						</div>

						<div class="card-body">

							<table id="example2" class="table">

								<thead>

									<tr>

										<th>upload ID</th>

										<th>Upload time stamp</th>

										<th>uploader</th>

										<th>Action</th>

									</tr>

								</thead>

								<tbody>

									<?php

									$query_uploader = "SELECT DISTINCT upload_id, upload__timestamp, uploader_id FROM project";

									$result_upload = $db_handle->runBaseQuery($query_uploader);



									if (!empty($result_upload)) {

										foreach ($result_upload as $key => $value) {

											?>

											<tr>

												<td><?php

												$upload_id = $result_upload[$key]["upload_id"];

												echo $upload_id;

												;

											?></td>

											<td><?php                                                       

											echo $result_upload[$key]["upload__timestamp"];

											;

										?></td>

										<td><?php

										$uploader_id = $result_upload[$key]["uploader_id"];

										$sql_uploader = "SELECT member_name, member_email FROM members WHERE member_id='$uploader_id'";

										$result_uploader = $db_handle->run($sql_uploader);

										while($row = mysqli_fetch_assoc($result_uploader)){

											$member_name = $row["member_name"];

											$member_email = $row["member_email"];

											echo $member_name." - ".$member_email;

										}



										;

									?></td>

									<td><?php

                                                            //add delete button                                                       

									echo "<form method='post' action='./deleteUpload.php' class='d-inline' onsubmit='return confirm(\"Delete this upload batch and all its projects?\")'>".csrf_field()."<input type='hidden' name='id' value='".$upload_id."'><button type='submit' class='btn btn-danger'>delete</button></form>";

									;

								?></td>





							</tr>

							<?php

						}

					}

					?>

				</tbody>

			</table>

		</div>

	</div><!-- /.card -->



</div>

<!-- col -->

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

<!-- Auto logout -->
<script src="../dist/js/auto-logout.js"></script>


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