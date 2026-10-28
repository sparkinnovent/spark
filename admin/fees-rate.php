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
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">GDIID - Fees Rate</h1>
				</div><!-- /.col -->
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>
						<li class="breadcrumb-item"><a href="./payment.php">Payment</a></li>
						<li class="breadcrumb-item active">Fees Rate</li>
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
						<div class="card-header clearfix">
							<h5 class="float-left mb-0">Fees Rate</h5>
							<button type="button" class="btn btn-warning btn-sm float-right" data-toggle="modal" data-target="#modal-add-payment-fees">
								<i class="fas fa-plus"></i> Add New Payment
							</button>
						</div>
						<div class="card-body ">
							<h6 class="card-title">List of Payment rate</h6>
							<p class="card-text"></p>
							<table id="example4" class="table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Category</th>
										<th>Date end</th>
										<th>Rate</th>
										<th>Link</th>
										<th>Remark</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$query = "SELECT * FROM payment_rate ORDER BY id DESC";
									$result_fees_rate = $db_handle->runBaseQuery($query);

									if (!empty($result_fees_rate)) {
										foreach ($result_fees_rate as $key => $value) {
											?>
											<tr>
												<td>
													<?php 
													$fees_id = $result_fees_rate[$key]["id"];
													echo $result_fees_rate[$key]["name"];
													?>
												</td>
												<td>
													<?php 
													echo $result_fees_rate[$key]["category"];
													?>
												</td>
												<td>
													<?php 
													echo $result_fees_rate[$key]["date_end"];
													?>
												</td>
												<td>
													<?php 
													echo $result_fees_rate[$key]["currency"]." ".$result_fees_rate[$key]["rate"];
													?>
												</td>
												<td>
													<?php
													$fee_link = $result_fees_rate[$key]["link"];
													echo "<a class='btn btn-info btn-sm' target='_blank' href='".$fee_link."'><i class='fas fa-link'></i></a>";
													?>
												</td>
												<td>
													<?php 
													echo $result_fees_rate[$key]["remark"];
													?>
												</td>
												<td>
													<a href="./update_fees.php?id=<?php echo $fees_id;?>" class="btn btn-warning btn-block btn-sm" onclick="return confirm('Are you sure you want to update this fee?');"><i class="fas fa-edit"></i></a>
													<a href="<?php echo csrf_url("./fees.process.php?delete&id=" . (int)$fees_id); ?>" class="btn btn-danger btn-block btn-sm" onclick="return confirm('Are you sure you want to delete this fee?');"><i class="fas fa-trash-alt"></i></a>
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



<div class="row">

	<div class="col-md-12">

	</div>

	<!-- /.col -->

</div>

<!-- /.row -->



</div><!--/. container-fluid -->

</section>
<!-- /.content -->

<!-- add payment fees rate modal -->
<div class="modal fade" id="modal-add-payment-fees">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Add New Fees</h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="form-add-member" method="post" action="./fees.process.php">
				<div class="modal-body">
					<small>make sure the fees name is unique</small>

					<div class="form-group">
						<label for="fees_name">Name</label>
						<input type="text" class="form-control" id="fees_name" name="fees_name" required />
					</div>

					<div class="form-group">
						<label for="fees_category">Category</label>                    
						<select id="fees_category" class="form-control" name="fees_category" required> 
							<option>Please Select</option>
							<option value="PUBLIC" >Public</option>
							<option value="UITMCTKT" >Uitmctkt</option>
						</select>                    
					</div>

					<div class="form-group">
						<label for="fees_date_end">Date End (optional)</label>                    
						<input type="date" class="form-control" id="fees_date_end" name="fees_date_end" />                    
					</div>

					<div class="form-group">
						<label for="fees_currency">Currency</label>                    
						<select name="fees_currency" class="form-control" id="fees_currency" >
							<option>Please Select</option>
							<option value="MYR">Malaysian Ringgit (MYR)</option>
							<option value="SGD">Singapore Dollar (SGD)</option>
							<option value="USD">US Dollar (USD)</option>
							<option value="AUD">Australian Dollar (AUD)</option>
							<option value="IDR">Indonesia Rupee (IDR)</option>
							<option value="BND">Brunei Dollar (BND)</option>
							<option value="THB">Thailand Baht (THB)</option>
						</select>                    
					</div>

					<div class="form-group">
						<label for="fees_rate">Rate</label>                    
						<input type="number" class="form-control" id="fees_rate" name="fees_rate" />                    
					</div>

					<div class="form-group">
						<label for="fees_rate">URL Link</label>                    
						<input type="text" class="form-control" id="url_link" name="url_link" />                    
					</div>

					<div class="form-group">
						<label for="fees_remarks">Remarks</label> 
						<textarea id="fees_remarks" name="fees_remarks" rows="3" class="form-control">
						</textarea>                                    
					</div>

					<div class="box-footer">

					</div>

				</div>
				<div class="modal-footer justify-content-end">
					<button type="button" class="btn btn-default" data-dismiss="modal">CLOSE</button>
					<button type="reset" class="btn btn-danger" name="reset" value="reset">
						<i class="fa fa-refresh"></i> RESET
					</button>
					<input type="submit" class="btn btn-primary" name="add" value="ADD" />
				</div>
			</form>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
<!-- /.modal -->


</div>

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
			"responsive": false,
			"scrollX": true,  // Enable horizontal scrolling 
			"lengthChange": true, 
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
		table4.buttons().container().appendTo('#example4_wrapper .col-md-6:eq(0)');

	});





</script>



</body>

</html>