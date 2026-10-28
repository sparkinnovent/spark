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
					<h1 class="m-0">GDIID - Presentation</h1>
				</div><!-- /.col -->
				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>
						<li class="breadcrumb-item active">Presentation</li>
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
							<h3 class="card-title">Presentation - Verify Presentation</h3>
							<div class="card-tools">
								<!-- Collapse Button -->
								<button type="button" class="btn btn-tool" data-card-widget="collapse">
									<i class="fas fa-minus"></i>
								</button>
							</div>
						</div>
						<div class="card-body">
							<h6 class="card-title">Verify Presentation</h6>
							<p class="card-text">List of participant which has uploaded presentation video link which has to validate the video link.</p>
							<table id="example4" class="table table-bordered table-striped">
								<thead>
									<tr>
										<th>Project</th>
										<th>Video File</th>
										<th>Accept</th> 
										<th>Reject</th>
									</tr>
								</thead>
								<tbody>

									<?php
									$query = "Select id, title, cat_iid, uitmctkt, country from project WHERE presentation_status='UPLOAD' order by id desc";
									$result2 = $db_handle->runBaseQuery($query);

									if (!empty($result2)) {
										foreach ($result2 as $key => $value) {
											$project_id = $result2[$key]["id"];
											?>
											<tr>
												<td>Project ID: <b><?php echo $project_id;?></b>
													Title: <b><?php echo $result2[$key]["title"]. "<br>"; ?></b>
													Catergory IID: <b><?php
													$cat_iid = $result2[$key]["cat_iid"];
													$cat_iid = $util->getCategory($cat_iid);
													echo $cat_iid;
												?></b>
												UITMCTKKT: <b><?php $uitmkt = $result2[$key]["uitmctkt"]; 
												if ($uitmkt) {
													echo "YES<br>";
												}else{
													echo "No<br>";
												}
											?></b>
											Country: <b><?php echo $result2[$key]["country"];?></b>

										</td>
										<td><?php
										//update 2025
										$sql_video = "SELECT * FROM project_presentation WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1";
										$result_video = $db_handle->run($sql_video);
										$video_id = null;
										$video_link = null;
										$recorded = null;
										$reject_reason = null;
										$reject_comment = null;
										while ($row_video = mysqli_fetch_assoc($result_video)) {
											$video_id = $row_video["id"];
											$video_link = $row_video["video_link"];
											$recorded = $row_video["recorded"];
											$reject_reason = $row_video["reject_reason"] ?? null;
											$reject_comment = $row_video["reject_comment"] ?? null;
										}

										// Display video link if available
										if (!empty($video_link)) {
											echo "<a target='_blank' href='" . htmlspecialchars($video_link) . "'>View <i class='fas fa-external-link-alt fa-xs'></i></a>";
										} else {
											echo "Not Available";
										}

										echo "<br><small class='text-muted'>Updated: " . (!empty($recorded) ? htmlspecialchars($recorded) : "N/A") . "</small>";

    									// Display reject reason and comment if available
										if (!empty($reject_reason) || !empty($reject_comment)) {
											echo "<br><strong class='text-danger'>Rejected Reason:</strong> " . (!empty($reject_reason) ? htmlspecialchars($reject_reason) : "N/A");
											echo "<br><strong class='text-danger'>Rejected Comment:</strong> " . (!empty($reject_comment) ? htmlspecialchars($reject_comment) : "N/A");
										}

										?>
									</td>
									<td>
										<form action="verify_presentation.process.php" method="post">
											<input type="hidden"  class="form-control" name="project_id" value="<?php echo $project_id;?>">
											<input type="hidden" name="video_id" value="<?php echo $video_id;?>" />
											<input type="text" class="form-control mb-2" name="youtube_link" placeholder="YouTube Link" required="">
											<input type="submit" class="form-control btn btn-success" name="video_accept" value="Accept" />
										</form>

									</td>
									<td>
										<form class="" action="verify_presentation.process.php?reject&id=<?php echo $project_id;?>" method="post">
											<div class="form-group">
												<label for="reason" >Reason</label>
												<select name="reason" id="reason" class="form-control" required="">
													<option>Please Select</option>
													<option value="Link not working">Link not working</option>
													<option value="Access denied">Access denied</option>
													<option value="unsupported Cloud Service">Use unsupported Cloud Service</option>
													<option value="Not in record">Not in record</option>
													<option value="Others">Others</option>
												</select>
											</div>
											<div class="form-group">
												<label for="comment">Comment</label>
												<textarea name="comment" id="comment" class="form-control" rows="2" ></textarea>
											</div>
											<input type="hidden" name="video_id" value="<?php echo $video_id;?>">
											<input type="submit" class="btn btn-danger pull-right" name="reject" value="Reject" />

										</form>                                      
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
<div class="col-md-12">
	<div class="card card-indigo card-outline" id="verified">
		<div class="card-header">
			<h3 class="card-title">Presentation - Verified Presentation</h3>
			<div class="card-tools">
				<!-- Collapse Button -->
				<button type="button" class="btn btn-tool" data-card-widget="collapse">
					<i class="fas fa-minus"></i>
				</button>
			</div>
		</div>
		<div class="card-body">
			<p class="card-text">List of participant which admin has verifed the presentation video link.</p>
			<table id="example5" class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>Project ID</th>
						<th>Email / Title</th>
						<th>Presentation</th>
						<th>Status</th>
						<th>Reject</th>
						<th>Verified By</th>
					</tr>
				</thead>
				<tbody>

					<?php
					$query = "SELECT p.id, p.email, p.title, p.vid_yt_link, p.presentation_status, 
					pp.reject_reason, pp.reject_comment, pp.verified_timestamp, 
					m.member_name, m.photo 
					FROM project p 
					LEFT JOIN project_presentation pp ON p.id = pp.project_id 
					LEFT JOIN members m ON pp.verified_by = m.member_id 
					WHERE p.presentation_status IN ('REMARK', 'ACCEPT', 'REJECT')
					ORDER BY p.id ASC";

					$result2 = $db_handle->runBaseQuery($query);

					if (!empty($result2)) {
						foreach ($result2 as $key => $value) {
							?>
							<tr>
								<td><?php echo $result2[$key]["id"]; ?></td>
								<td>
									<?php
									echo "Email: " . htmlspecialchars($result2[$key]["email"]);
									echo "<br>";
									echo "Title: " . htmlspecialchars($result2[$key]["title"]);
									?>
								</td>
								<td>
									<?php
									$yt_link = $result2[$key]["vid_yt_link"];
									if (!empty($yt_link)) {
										echo "<a target='_blank' href='" . htmlspecialchars($yt_link) . "'>" . htmlspecialchars($yt_link) . "</a>";
									} else {
										echo "Not Available";
									}
									?>
								</td>
								<td><?php echo htmlspecialchars($result2[$key]["presentation_status"]); ?></td>
								<td>
									<?php
									if ($result2[$key]["presentation_status"] === "REMARK") {
										echo "<strong>Reject Reason:</strong> " . htmlspecialchars($result2[$key]["reject_reason"]) . "<br>";
										echo "<strong>Reject Comment:</strong> " . htmlspecialchars($result2[$key]["reject_comment"]);
									} else {
										echo "N/A";
									}
									?>
								</td>
								<td>       
									
									<div class="d-flex align-items-center">
										<img src="dist/<?php echo htmlspecialchars($result2[$key]["photo"]); ?>" class="img-thumbnail" width="50" height="50" style="border-radius:50%; margin-right:10px;">
										<div>
											<span><?php echo htmlspecialchars($result2[$key]["member_name"]); ?></span><br>
											<small style="color: gray;"><?php echo htmlspecialchars($result2[$key]["verified_timestamp"]); ?></small>
										</div>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>

				</tbody>
			</table>

		</div><!-- card-body -->
	</div>
<!-- card -->
</div>
<!-- col-12 -->
</div>
<!-- /.row -->
<!-- Main row -->

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