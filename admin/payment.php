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
	<div class="content-header">
		<div class="container-fluid">
			<?php Notify::display();?>
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">GDIID - Payment</h1>
				</div><!-- /.col -->
				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>
						<li class="breadcrumb-item active">Payment</li>
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
							<h3 class="card-title">Payment - Verify Payment</h3>
							<div class="card-tools">
								<!-- Collapse Button -->
								<button type="button" class="btn btn-tool" data-card-widget="collapse">
									<i class="fas fa-minus"></i>
								</button>
							</div>
						</div>
						<div class="card-body">
							<h6 class="card-title">Verify Payment</h6>
							<p class="card-text">List of participant which has uploaded proof of payment which has to validate the payment from the participant.</p>
							<table id="example4" class="table table-bordered table-striped">
								<thead>
									<tr>
										<th>Payment Info</th>
										<th>Project</th>
										<th>Proof payment</th>
										<th>Accept</th> 
										<th>Reject</th>
									</tr>
								</thead>
								<tbody>
									<?php
									//$query = "Select a.title, a.currency, a.cat_iid, a.organisation, a.uitmctkt, a.fees AS p_fees, b.* from project a JOIN project_payment b ON a.id=b.project_id WHERE a.payment_status='UPLOAD' OR a.payment_status='REMARK' order by a.id desc";
									//query 2025
									$query_user_payment = "Select a.title, a.currency, a.cat_iid, a.organisation, a.uitmctkt, a.fees AS p_fees, a.country, b.* from project a JOIN project_payment b ON a.id=b.project_id WHERE a.payment_status='UPLOAD' OR a.payment_status='REMARK' order by a.id desc";
									$result_user_payment = $db_handle->run($query_user_payment);
									while($row_user_payment = mysqli_fetch_assoc($result_user_payment)){


										?>
										<tr>
											<td>
												<?php 
												$payment_date =  $row_user_payment["payment_date"];
												$uitmkt =  $row_user_payment["uitmctkt"];
												$pay_currency = $row_user_payment["currency"];
												$user_payment_method = $row_user_payment["payment_method"];
										//update 2025
												$sql_payment_rate = "SELECT * FROM payment_rate WHERE id='$user_payment_method'";
										//echo $sql_payment_rate;
												$result_payment_rate = $db_handle->run($sql_payment_rate);
												$row_payment_rate = mysqli_fetch_assoc($result_payment_rate);
												$rate_name = $row_payment_rate["name"];
												$rate_category = $row_payment_rate["category"];
												$rate_date_end = $row_payment_rate["date_end"];
												$rate_currency = $row_payment_rate["currency"];
												$rate_rate = $row_payment_rate["rate"];

												?>
												Payment Date: <br><b><?php echo $payment_date;?></b><br>
												Payment Rate: <br><b><?php echo $rate_currency . " " . $rate_rate;?></b><br>
												Fee Category: <br><b><?php echo $rate_category;?></b><br>
												Payment Method: <br><b><?php echo $rate_name;?></b><br>

											</td>
											<td>
												Project ID: <b><?php echo $row_user_payment["project_id"];?></b><br>
												Title: <b><?php echo $row_user_payment["title"]. "<br>"; ?></b>
												Catergory IID: <b><?php
												$cat_iid = $row_user_payment["cat_iid"];
												$cat_iid = $util->getCategory($cat_iid);
												echo $cat_iid;?></b>
												<br>
												Organisation: <b><?php echo $row_user_payment["organisation"];?></b><br>
												Country: <b><?php echo $row_user_payment["country"];?></b><br>
												UITMCTKKT: <b><?php $uitmkt = $row_user_payment["uitmctkt"]; 
												if ($uitmkt) {
													echo "YES<br>";
												}else{
													echo "NO<br>";
												}
											?></b>
										</td>
										<td><?php                                      
										$source = $row_user_payment['payment_proof'];                                
										//echo "<a target='_blank' href='../upload/payment/".$source."'>".$source."</a>";
										?>
										<!-- Button to trigger modal -->
										<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#paymentProofModal<?php echo $row_user_payment['id']; ?>">
											<i class="fas fa-eye"></i> View Payment Proof
										</button>
										<!-- Modal Structure -->
										<div class="modal fade" id="paymentProofModal<?php echo $row_user_payment['id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $row_user_payment['id']; ?>" aria-hidden="true">
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header">
														<h5 class="modal-title" id="modalLabel<?php echo $row_user_payment['id']; ?>">Payment Proof</h5>
														<button type="button" class="close" data-dismiss="modal" aria-label="Close">
															<span aria-hidden="true">&times;</span>
														</button>
													</div>
													<div class="modal-body text-center">
														<!-- Show Image or Download Link -->
														<?php
														$proof_file_type = $row_user_payment["payment_type"];
														$proof_file_path = "../upload/payment/" . htmlspecialchars($source);
														if (!empty($source)) :
															if ($proof_file_type == "IMAGE") : 
																?>
																<!-- Display Image -->
																<img src="<?php echo $proof_file_path; ?>" alt="Payment Proof" class="img-fluid">
																<?php 
															elseif ($proof_file_type == 'PDF') : 
																?>
																<!-- Display PDF in an iframe -->
																<iframe src="<?php echo $proof_file_path; ?>" width="100%" height="500px"></iframe>
																<?php 
															else :
																?>
																<p class="text-warning">Unsupported file format.</p>
																<?php 
															endif; 
															?>

															<!-- Download Button -->
															<p class="mt-2">
																<a href="<?php echo $proof_file_path; ?>" target="_blank" class="btn btn-success btn-sm">
																	<i class="fas fa-download"></i> Download
																</a>
															</p>

														<?php else : ?>
															<p class="text-danger">No payment proof available.</p>
														<?php endif; ?>
													</div>
												</div>
											</div>
										</div>
									</div>


								</td>
								<td>
									<form action="verify_payment.process.php" method="post" >
										<input type="hidden" name="id" value="<?php echo $row_user_payment["project_id"];?>">
										<input type="hidden" name="payment_id" value="<?php echo $row_user_payment["id"];?>">
										<input type="hidden" name="payment_rate" value="<?php echo $user_payment_method;?>">
										<input type="submit" class="btn btn-success btn-block" name="accept" value="Accept" onclick="return confirm('Are you sure you want to accept this payment?');" />
									</form>
								</td>
								<td>
									<form class="" action="verify_payment.process.php?reject&id=<?php echo $row_user_payment["project_id"];?>&payment_id=<?php echo $row_user_payment["id"];?>" method="post">
										<div class="form-group">
											<label for="reason" >Reason</label>
											<select name="reason" id="reason" class="form-control" required>
												<option value="">-- Please Select a Reason --</option>
												<option value="Blurry or unreadable proof">Proof of payment is blurry or unreadable</option>
												<option value="Incorrect proof uploaded">Uploaded proof of payment is incorrect</option>
												<option value="Insufficient payment amount">Payment amount does not match the required fee</option>
												<option value="No matching record found">Payment record not found in our system</option>
												<option value="Payment details mismatch">Payment details do not match the provided project details</option>
												<option value="Other">Other (please specify in the comments)</option>
											</select>
										</div>
										<div class="form-group">
											<label for="comment">Comment</label>
											<textarea name="comment" id="comment" class="form-control" rows="2" ></textarea>
										</div>
										<input type="submit" class="btn btn-danger btn-block mb-2" name="reject" value="Reject" />
									</form> 

									<a class="btn btn-warning btn-block" href="./verify_payment.process.php?delete=<?php echo $row_user_payment["id"];?>&file_name=<?php echo $source;?>" onclick="return confirm('Are you sure you want to delete this payment record? This action is irreversible.');"> Delete</a>                                     
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
<!-- card-body -->
</div>
<!-- card -->
</div>
<!-- col-12 -->

<div class="col-md-12">
	<div class="card card-indigo card-outline" id="verified">
		<div class="card-header">
			<h3 class="card-title">Payment - Verified Payment</h3>
			<div class="card-tools">
				<!-- Collapse Button -->
				<button type="button" class="btn btn-tool" data-card-widget="collapse">
					<i class="fas fa-minus"></i>
				</button>
			</div>
		</div>
		<div class="card-body">
			<h6 class="card-title">Verified Payment List</h6>
			<p class="card-text">List of participant which admin has verifed the proof of payment.</p>
			<table id="example5" class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>Project ID</th>
						<th>EMail / Title</th>
						<th>Payment Info</th>
						<th>Status</th>
						<th>Remarks (if Rejected)</th>
						<th>Verified By</th>
						<th>Verified Date Time</th> 
					</tr>
				</thead>
				<tbody>

					<?php
					$query = "SELECT p.id, p.email, p.title, p.status, p.payment_status, pp.payment_date, pp.payment_proof, pp.reject_reason, pp.reject_comment, pp.verification_timestamp, m.member_name FROM project p LEFT JOIN project_payment pp ON p.id = pp.project_id JOIN members m ON pp.verified_by = m.member_id ORDER BY p.id ASC";
					$result2 = $db_handle->runBaseQuery($query);

					if (!empty($result2)) {
						foreach ($result2 as $key => $value) {
							?>
							<tr>
								<td><?php
								$id = $result2[$key]["id"];
								echo $id;
								;
							?></td>
							<td><?php                                                       
							echo "Email: ".$result2[$key]["email"];
							echo "<br>";
							echo "Title: ".$result2[$key]["title"];
						?></td>
						<td><?php                                                       
						echo "Date: ".$result2[$key]["payment_date"];
						echo "<br>";
						$payment_proof = $result2[$key]["payment_proof"];
						echo "Proof: <a target='_blank' href='../upload/payment/".$payment_proof."' >".$payment_proof."</a>";
					?></td>
					<td><?php
					$status = $result2[$key]["payment_status"];
					echo $status;
					?>
				</td>
				<td>
					<p>If the status is ACCEPT, there is NO Remark</p>
					<?php
					$reject_reason = $result2[$key]["reject_reason"];
					$reject_comment = $result2[$key]["reject_comment"];
					echo "Reject Reason: ".$reject_reason;
					echo "<BR>";
					echo "Reject Comment: ".$reject_comment;
					?>
				</td>
				<td><?php
				$verified_by = $result2[$key]["member_name"];
				echo $verified_by;
				?>
			</td>
			<td><?php
			$verified_datetime = $result2[$key]["verification_timestamp"];
			echo $verified_datetime;
			?>
		</td>

	</tr>
	<?php
}
}
?>
</tbody>
</table>
</div>
<!-- card-body -->
</div>
<!-- card -->

</div>
<!-- col-12 -->
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