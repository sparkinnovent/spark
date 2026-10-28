<?php

session_start();

require_once '../Dbcontroller.php';

require_once '../Util.php';

require_once './Usersession.php';

require_once '../Notify.php';



$db_handle = new DBController();

$util = new Util();

$user = new Usersession();

//block access without session
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

					<h1 class="m-0">GDIID - HOME</h1>

				</div><!-- /.col -->

				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">

					<ol class="breadcrumb float-sm-right">

						<li class="breadcrumb-item"><a href="./">Home</a></li>

						

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

				<div class="col-md-12">

					<?php Notify::display();?>



					<div class="card card-primary card-outline">

						<div class="card-header">

							<h5>Participant List</h5>

						</div>

						<div class="card-body">

							<h6 class="card-title">Home Page GDIID ADMIN</h6>

							<p class="card-text">Please select the menu at your left.</p>

							<p class="card-text">Currently, no idea yet for this page. feel free to suggest any information can be shown here.</p>			

						</div>

					</div>

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

	});





</script>



</body>

</html>