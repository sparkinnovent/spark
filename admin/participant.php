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

$login = $user->getSession();
if (!$login) {
	$util->gotohome();
}
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

					<h1 class="m-0">GDIID - Participant</h1>

				</div><!-- /.col -->

				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">

					<ol class="breadcrumb float-sm-right">

						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>

						<li class="breadcrumb-item active">Participant</li>

					</ol>

				</div><!-- /.col -->

			</div><!-- /.row -->

		</div><!-- /.container-fluid -->

	</div>

	<!-- /.content-header -->



	<!-- Main content -->

	<section class="content">

		<div class="container-fluid">
			<?php Notify::display();?>
			<div class="row">

				<div class="col-md-12">



					<div class="card card-primary card-outline">

						<div class="card-header">

							<h5>Participant List</h5>

						</div>

						<div class="card-body">

							<h6 class="card-title">List of Participant was uploaded</h6>

							<p class="card-text">Please make sure the list is up to date.</p>



							<table id="example5" class="table table-bordered table-striped">

								<thead>

									<tr>
										<th>ID</th>
										<th>Corresponding Email</th>
										<th>Title</th>
										<th>Category</th>
										<th>Status</th>
										<th>LOA Status</th>
										<th>Payment Status</th>
										<th>Presentation Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>

									<?php
									//update 2025 - unicode
									$db_handle->setCharset("utf8mb4");
									$query = "Select * from project order by id ASC";
									$result_project_list = $db_handle->runBaseQuery($query);
									if (!empty($result_project_list)) {
										foreach ($result_project_list as $key => $value) {
											$country = $result_project_list[$key]["country"];
											$international = 0;
											if ($country != "MALAYSIA") {
												$international = 1;
											}
											?>
											<tr>
												<td><?php
												$id = $result_project_list[$key]["id"];
												echo $id;
											?></td>
											<td><?php
											echo $result_project_list[$key]["email"];                
										?></td>
										<td><?php                
										echo $result_project_list[$key]["title"];
									?></td>
									<td><?php
									echo $result_project_list[$key]["cat_iid"];
								?></td>
								<td>       
									<?php
									echo $result_project_list[$key]["status"];
									?>
								</td>
								<td>       
									<?php
									echo $result_project_list[$key]["loa_status"];
									?>
								</td>
								<td>
									<?php
									echo $result_project_list[$key]["payment_status"];					
									?>
								</td>
								<td>
									<?php
									echo $result_project_list[$key]["presentation_status"];					
									?>
								</td>
								<td>
									<div class="d-grid gap-2">
										<!-- Update Project -->
										<a href="update_project.php?id=<?php echo $id;?>" 
											class="btn btn-warning btn-sm btn-block" title="Update Project" onclick="return confirm('When you update this project, please make sure to generate the LOA again when if relevent.');">
											<i class="fa fa-pen"></i> Update Project
										</a>

										<!-- Delete Project -->
										<form method="post" action="deleteProject.php" class="d-block"
											onsubmit="return confirm('Are you sure you want to delete this project?');">
											<?php echo csrf_field(); ?>
											<input type="hidden" name="id" value="<?php echo (int)$id; ?>">
											<button type="submit" class="btn btn-danger btn-sm btn-block" title="Delete Project">
												<i class="far fa-trash-alt"></i> Delete Project
											</button>
										</form>
									</div>

								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<!-- /.col -->
</div>
<!-- /.row -->
<!-- Main row -->
<div class="row">

	<!-- Left col -->

	<div class="col-md-8">



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
		table4.buttons().container().appendTo('#example4_wrapper .col-md-6:eq(0)');

		var table5 = $("#example5").DataTable({
			"responsive": false,
			"scrollX": true,  // Enable horizontal scrolling
			"lengthChange": false,
			"autoWidth": false,
			"buttons" : [
				{ extend: 'copy', className: 'btn btn-success' },
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' },
				{ extend: 'print', className: 'btn btn-success' },
				{ extend: 'colvis', className: 'btn btn-success' }
			]
		});
		table5.buttons().container().appendTo('#example5_wrapper .col-md-6:eq(0)');

	});





</script>



</body>

</html>