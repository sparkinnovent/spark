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

//block access without session
$login = $user->getSession();
if (!$login) {
	$util->gotohome();
}

if (isset($_GET["tab_ex"])) {
	$active_tab_ex = $_GET["tab_ex"];
}else{
	$active_tab_ex  = "tab1";
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
					<h1 class="m-0">GDIID - Extended Abstract</h1>
				</div><!-- /.col -->
				<div class="col-sm-6" data-spm-anchor-id="a21bo.29368746.0.i24.71041859yqRJD1">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="./secure.php">Home</a></li>
						<li class="breadcrumb-item active">Extended Abstract</li>
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
					<div class="card card-primary card-outline card-outline-tabs">
						<div class="card-header p-0 border-bottom-0">
							<ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
								<li class="nav-item">
									<a class="nav-link <?php if ($active_tab_ex == "tab1") {echo 'active';}?>" id="custom-tabs-four-home-tab" data-toggle="pill" href="#custom-tabs-four-home" role="tab" aria-controls="custom-tabs-four-home" aria-selected="true">1. Verify File/Turnitin</a>
								</li>
								<li class="nav-item">
									<a class="nav-link <?php if ($active_tab_ex == "tab2") {echo 'active';}?>" id="custom-tabs-four-profile-tab" data-toggle="pill" href="#custom-tabs-four-profile" role="tab" aria-controls="custom-tabs-four-profile" aria-selected="false">2. Reviewer Comment</a>
								</li>
								<li class="nav-item">
									<a class="nav-link <?php if ($active_tab_ex == "tab3") {echo 'active';}?>" id="custom-tabs-four-messages-tab" data-toggle="pill" href="#custom-tabs-four-messages" role="tab" aria-controls="custom-tabs-four-messages" aria-selected="false">3. Verify Correction</a>
								</li>
								<li class="nav-item">
									<a class="nav-link <?php if ($active_tab_ex == "tab4") {echo 'active';}?>" id="custom-tabs-four-settings-tab" data-toggle="pill" href="#custom-tabs-four-settings" role="tab" aria-controls="custom-tabs-four-settings" aria-selected="false">4. Complete</a>
								</li>
							</ul>
						</div>
						<div class="card-body">
							<div class="tab-content" id="custom-tabs-four-tabContent">
								<div class="tab-pane fade <?php if ($active_tab_ex == "tab1") {echo 'active show';}?>" id="custom-tabs-four-home" role="tabpanel" aria-labelledby="custom-tabs-four-home-tab">

									<div class="row mb-3">
										<div class="col-sm-8"><h3 id="ver_incomplete_abstract_tab1" class="card-title">Incomplete Verification</h3></div>
										<div class="col-sm-4"><a href="#ver_complete_abstract_tab1" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Completed</a></div>
									</div>

									<table id="example1" class="table table-bordered table-striped">
										<thead>
											<tr>
												<th>Project</th>
												<th>Extended Abstract</th>
												<th>Turnitin Report</th>
												<th>Accept</th> 
												<th>Reject</th>
											</tr>
										</thead>
										<tbody>

											<?php
											$query = "Select id, title, cat_iid, uitmctkt, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='UPLOAD' OR abstract_status='REMARK' order by id desc";
											$result2 = $db_handle->runBaseQuery($query);

											if (!empty($result2)) {
												foreach ($result2 as $key => $value) {
													?>
													<tr>

														<td>Project ID: <b><?php echo $result2[$key]["id"];?></b>
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

												</td>
												<td><?php                                      
												$ex_abstract_file = $result2[$key]['ex_abstract_file'];
												echo '<a 
												class="btn btn-warning btn-block mb-1" 
												target="_blank" 
												href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/' . $ex_abstract_file . '" 
												data-bs-toggle="tooltip" 
												title="Note: Some .docx files may not open correctly in Google Docs preview.">
												<i class="fab fa-google-drive"></i> Google Docs
												</a>';
												echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

												?>
											</td>
											<td><?php                                      
											$turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
											echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
											?>
										</td>
										<td>
											<a href="../verify_ex_abstract.process.php?accept&id=<?php echo $result2[$key]["id"];?>" class="btn btn-success" data-toggle="tooltip" title="accept" > <i class="fa fa-check"></i></a>

										</td>
										<td>
											<form class="" action="../verify_ex_abstract.process.php?reject&id=<?php echo $result2[$key]["id"];?>" method="post">
												<div class="form-group">
													<label for="reason" >Reason</label>
													<select name="reason" id="reason" class="form-control" required="">
														<option >Please Select</option>
														<option value="Turnitin similarity index exceed 30%">Turnitin similarity index exceed 30%</option>
														<option value="Not well-formatted">Not well-formatted</option>
														<option value="Wrong document(s) uploaded">Wrong document(s) uploaded</option>
														<option value="Others">Others</option>
													</select>
												</div>
												<div class="form-group">
													<label for="comment">Comment</label>
													<textarea name="comment" id="comment" class="form-control" rows="2" ></textarea>
												</div>

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


					<div class="row mb-3 mt-3">
						<div class="col-sm-8"><h3 id="ver_complete_abstract_tab1" class="card-title">Completed Verification</h3></div>
						<div class="col-sm-4"><a href="#ver_incomplete_abstract_tab1" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Incomplete</a></div>
					</div>

					<table id="example2" class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Project ID</th>
								<th>Title</th>
								<th>Category</th>
								<th>Email</th>
								<th>Author(s)</th>
								<th>Extended Abstract</th>
								<th>Turnitin Report</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>

							<?php
							$query = "Select id, title, cat_iid, uitmctkt, abstract_status, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='ACCEPT' order by id desc";
							$result2 = $db_handle->runBaseQuery($query);

							if (!empty($result2)) {
								foreach ($result2 as $key => $value) {
									?>
									<tr>
										<td><?php
										$project_id_tab1_kakya = $result2[$key]["id"];
										echo $project_id_tab1_kakya;?></td>
										<td><?php echo $result2[$key]["title"];?></td>
										<td><?php  
										$cat_iid = $result2[$key]["cat_iid"];
										$cat_iid = $util->getCategory($cat_iid);
										echo $cat_iid;?></td>
										<td><?php echo $result2[$key]["email"];?></td>

										<td>
											<?php 

											$sql_authors = "SELECT name FROM project_member WHERE project_id = '$project_id_tab1_kakya' ORDER BY position ASC";
											$result_authors_tab1 = $db_handle->run($sql_authors);
											$author_tab1_kakya = NULL;
											while ($row_tab1_kakya = mysqli_fetch_assoc($result_authors_tab1)) {
												if ($author_tab1_kakya == NULL) {
													$author_tab1_kakya = $row_tab1_kakya["name"];
												}else{
													$author_tab1_kakya = $author_tab1_kakya. ",<br>". $row_tab1_kakya["name"];
												}
											}

											echo $author_tab1_kakya;
											?>
										</td>

										<td><?php                                      
										$ex_abstract_file = $result2[$key]['ex_abstract_file'];
										echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

										echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

										?>
									</td>
									<td><?php                                      
									$turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
									echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
									?>
								</td>
								<td><?php echo $result2[$key]["abstract_status"];?></td>

							</tr>
							<?php
						}
					}
					?>

				</tbody>

<!-- tab2 - ajk upload review comment. -->
</table>
</div>
<div class="tab-pane <?php if ($active_tab_ex == "tab2") {echo 'active show';}?> fade" id="custom-tabs-four-profile" role="tabpanel" aria-labelledby="custom-tabs-four-profile-tab">

	<div class="row mb-3">
		<div class="col-sm-8"><h3 id="ver_incomplete_abstract_tab2" class="card-title">Incomplete Verification</h3></div>
		<div class="col-sm-4"><a href="#ver_complete_abstract_tab2" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Completed</a></div>
	</div>

	<table id="example3" class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>Project</th>
				<th>Extended Abstract</th>
				<th>Turnitin Report</th>
				<th>Action</th> 
			</tr>
		</thead>
		<tbody>

			<?php
			$query = "Select id, title, email, cat_iid, uitmctkt, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='ACCEPT' order by id desc";
			$result2 = $db_handle->runBaseQuery($query);

			if (!empty($result2)) {
				foreach ($result2 as $key => $value) {

					$project_id_tab2 = $result2[$key]["id"];
					$title_tab2 = $result2[$key]["title"];
					$email_tab2 = $result2[$key]["email"];
					$cat_iid_tab2 = $result2[$key]["cat_iid"];
					$cat_iid_tab2 = $util->getCategory($cat_iid_tab2);
					$cat_iid_tab2 = strtoupper($cat_iid_tab2);
					$uitmctkt_tab2 = $result2[$key]["uitmctkt"];

					if ($uitmctkt_tab2) {
						$uitmctkt_tab2 = "YES";
					}else{
						$uitmctkt_tab2 = "NO";
					}

					?>
					<tr>

						<td>

							<table class="table table-sm table-borderless">
								<tr>
									<td>Project ID</td>
									<td><?php echo $project_id_tab2;?></td>
								</tr>
								<tr>
									<td>Title</td>
									<td><?php echo $title_tab2;?></td>
								</tr>
								<tr>
									<td>Email</td>
									<td><?php echo $email_tab2;?></td>
								</tr>
								<tr>
									<td>Category</td>
									<td><?php echo $cat_iid_tab2;?></td>
								</tr>
								<tr>
									<td>UITMCTKKT</td>
									<td><?php echo $uitmctkt_tab2;?></td>
								</tr>
							</table>
						</td>
						<td><?php                                      
						$ex_abstract_file = $result2[$key]['ex_abstract_file'];
						echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

						echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

						?>
					</td>
					<td><?php                                      
					$turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
					echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
					?>
				</td>
				<td>

					<form action="../verify_ex_abstract.process.php" method="post" enctype="multipart/form-data">
						<div class="form-group">
							<label for="status">Status</label>
							<small class="text-muted">please select extended abstract status</small>
							<select id="status" class="form-control" required="" name="review_status">
								<option>Please Select</option>
								<option value="complete">Accept without correction</option>
								<option value="review">Accept with correction</option>
								<option value="reject">Reject</option>
							</select>
						</div>
						<div class="form-group">
							<label for="review_file">Review File</label>
							<small class="text-muted">only accept PDF file</small>
							<input type="file" class="form-control" id="review_file" name="review_file" accept="application/pdf">
						</div>
						<input type="hidden" name="id" value="<?php echo $project_id_tab2;?>">
						<input type="submit" class="btn btn-primary btn-block" name="submit_review" value="Send Review">
					</form>

					<hr class="mt-1 mb-1"/>

					<a href="../verify_ex_abstract.process.php?complete_no_review&id=<?php echo $project_id_tab2;?>" class="btn btn-success btn-block">Accept without correction without reviewer comment (file)</a>
				</td>

			</tr>
			<?php
		}
	}
	?>

</tbody>

</table>


<div class="row mb-3 mt-3">
	<div class="col-sm-8"><h3 id="ver_complete_abstract_tab2" class="card-title">Completed Verification</h3></div>
	<div class="col-sm-4"><a href="#ver_incomplete_abstract_tab2" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Incomplete</a></div>
</div>

<table id="example4" class="table table-bordered table-striped">
	<thead>
		<tr>
			<th>Project ID</th>
			<th>Title</th>
			<th>Category</th>
			<th>Email</th>
			<th>Ex. Abstract/Turitin</th>
			<th>Review File</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>

		<?php
		$query = "SELECT p.id, p.title, p.cat_iid, p.uitmctkt, p.abstract_status, p.email, p.ex_abstract_file, p.turnitin_file, pr.review_file from project p JOIN project_ex_abstract_review pr ON p.id=pr.project_id WHERE p.abstract_status='REVIEW' order by p.id desc";
		$result2 = $db_handle->runBaseQuery($query);

		if (!empty($result2)) {
			foreach ($result2 as $key => $value) {
				?>
				<tr>
					<td><?php echo $result2[$key]["id"];?></td>
					<td><?php echo $result2[$key]["title"];?></td>
					<td><?php  
					$cat_iid = $result2[$key]["cat_iid"];
					$cat_iid = $util->getCategory($cat_iid);
					echo $cat_iid;?></td>
					<td><?php echo $result2[$key]["email"];?></td>

					<td><?php                                      
					$ex_abstract_file = $result2[$key]['ex_abstract_file'];
					echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

					echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';

					$turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
					echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> Turnitin </a>';
					?>
				</td>
				<td><?php                                      
				$review_file = $result2[$key]['review_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
				echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/review/'.$review_file.'"><i class="fas fa-eye"></i> View</a>';
				?>
			</td>
			<td><?php echo $result2[$key]["abstract_status"];?></td>

		</tr>
		<?php
	}
}
?>

</tbody>

</table>

</div>
<!-- tab review correction -->
<div class="tab-pane <?php if ($active_tab_ex == "tab3") {echo 'active show';}?> fade" id="custom-tabs-four-messages" role="tabpanel" aria-labelledby="custom-tabs-four-messages-tab">

	<div class="row mb-3">
		<div class="col-sm-8"><h3 id="ver_incomplete_abstract_tab3" class="card-title">Incomplete Verification</h3></div>
		<div class="col-sm-4"><a href="#ver_complete_abstract_tab3" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Rejected Correction</a></div>
	</div>

	<table id="example5" class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>Project</th>
				<th>Extended Abstract</th>
				<th>Review Report</th>
				<th>Accept</th>
				<th>Reject</th> 
			</tr>
		</thead>
		<tbody>

			<?php
			$query = "SELECT p.id, p.title, p.cat_iid, p.uitmctkt, p.abstract_status, p.email, p.ex_abstract_file, p.turnitin_file, pr.review_file from project p JOIN project_ex_abstract_review pr ON p.id=pr.project_id WHERE p.abstract_status='CORRECTION' order by p.id desc";
			$result_ex_abstract_tab3 = $db_handle->runBaseQuery($query);

			if (!empty($result_ex_abstract_tab3)) {
				foreach ($result_ex_abstract_tab3 as $key => $value) {
					$project_id_tab3 = $result_ex_abstract_tab3[$key]["id"];
					?>
					<tr>

						<td>Project ID: <b><?php echo $project_id_tab3;?></b>
							Title: <b><?php echo $result_ex_abstract_tab3[$key]["title"]. "<br>"; ?></b>
							Catergory IID: <b><?php
							$cat_iid = $result_ex_abstract_tab3[$key]["cat_iid"];
							$cat_iid = $util->getCategory($cat_iid);
							echo $cat_iid. "<br>";
						?></b>

						UITMCTKKT: <b><?php $uitmkt = $result_ex_abstract_tab3[$key]["uitmctkt"]; 
						if ($uitmkt) {
							echo "YES<br>";
						}else{
							echo "No<br>";
						}
					?></b>

				</td>
				<td><?php                                      
				$ex_abstract_file = $result_ex_abstract_tab3[$key]['ex_abstract_file'];
				echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

				echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

				?>
			</td>
			<td><?php                                      
			$review_file = $result_ex_abstract_tab3[$key]['review_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
			echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/review/'.$review_file.'"><i class="fas fa-eye"></i> View</a>';
			?>
		</td>
		<td>
			<a href="../verify_ex_abstract.process.php?accept_correction&id=<?php echo $project_id_tab3;?>" class="btn btn-success btn-block">ACCEPT</a>
		</td>
		<td>
			<form action="../verify_ex_abstract.process.php" method="post">
				<div class="form-group">
					<label for="inputtextcomment">Comment</label>
					<textarea class="form-control" name="reject_comment" id="inputtextcomment" required="" rows="3"></textarea>
				</div>
				<input type="hidden" name="id" value="<?php echo $project_id_tab3;?>">
				<input type="submit" class="btn btn-primary btn-block" name="submit_reject_correction" value="Submit Reject">
			</form>
		</td>

	</tr>
	<?php
}
}
?>

</tbody>

</table>


<div class="row mb-3 mt-3">
	<div class="col-sm-8"><h3 id="ver_complete_abstract_tab3" class="card-title">Rejected Correction</h3></div>
	<div class="col-sm-4"><a href="#ver_incomplete_abstract_tab3" class="btn btn-primary float-right" ><i class="far fa-eye"></i>Incomplete Correction Review</a></div>
</div>

<table id="example6" class="table table-bordered table-striped">
	<thead>
		<tr>
			<th>Project ID</th>
			<th>Title</th>
			<th>Category</th>
			<th>Email</th>
			<th>Extended Abstract</th>
			<th>Turnitin Report</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>

		<?php
		$query = "Select id, title, cat_iid, uitmctkt, abstract_status, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='CORRECTION_REMARK' order by id desc";
		$result2 = $db_handle->runBaseQuery($query);

		if (!empty($result2)) {
			foreach ($result2 as $key => $value) {
				?>
				<tr>
					<td><?php echo $result2[$key]["id"];?></td>
					<td><?php echo $result2[$key]["title"];?></td>
					<td><?php  
					$cat_iid = $result2[$key]["cat_iid"];
					$cat_iid = $util->getCategory($cat_iid);
					echo $cat_iid;?></td>
					<td><?php echo $result2[$key]["email"];?></td>

					<td><?php                                      
					$ex_abstract_file = $result2[$key]['ex_abstract_file'];
					echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

					echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

					?>
				</td>
				<td><?php                                      
				$turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
				echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
				?>
			</td>
			<td><?php echo $result2[$key]["abstract_status"];?></td>

		</tr>
		<?php
	}
}
?>

</tbody>

</table>

</div>
<div class="tab-pane <?php if ($active_tab_ex == "tab4") {echo 'active show';}?> fade" id="custom-tabs-four-settings" role="tabpanel" aria-labelledby="custom-tabs-four-settings-tab">

	<table id="example7" class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>Project ID</th>
				<th>Title</th>
				<th>Category</th>
				<th>Email</th>
				<th>Extended Abstract</th>
				<th>Turnitin Report</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>

			<?php
			$query = "Select id, title, cat_iid, uitmctkt, abstract_status, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='COMPLETE' order by id desc";
			$result2 = $db_handle->runBaseQuery($query);

			if (!empty($result2)) {
				foreach ($result2 as $key => $value) {
					?>
					<tr>
						<td><?php echo $result2[$key]["id"];?></td>
						<td><?php echo $result2[$key]["title"];?></td>
						<td><?php  
						$cat_iid = $result2[$key]["cat_iid"];
						$cat_iid = $util->getCategory($cat_iid);
						echo $cat_iid;?></td>
						<td><?php echo $result2[$key]["email"];?></td>

						<td><?php                                      
						$ex_abstract_file = $result2[$key]['ex_abstract_file'];
						echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

						echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

						?>
					</td>
					<td><?php                                      
					$turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
					echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
					?>
				</td>
				<td><?php echo $result2[$key]["abstract_status"];?></td>

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
<!-- /.card -->
</div>
</div>

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
			"buttons" : [
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' }
			]
		});
		table1.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

		var table2 = $("#example2").DataTable({
			"responsive": true, 
			"lengthChange": false, 
			"autoWidth": false,
			"buttons" : [
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' }
			]
		});
		table2.buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');

		var table3 = $("#example3").DataTable({
			"responsive": true, 
			"lengthChange": false, 
			"autoWidth": false,
			"buttons" : [
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' }
			]
		});
		table3.buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');

		var table4 = $("#example4").DataTable({
			"responsive": true, 
			"lengthChange": false, 
			"autoWidth": false,
			"buttons" : [
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' }
			]
		});
		table4.buttons().container().appendTo('#example4_wrapper .col-md-6:eq(0)');

		var table5 = $("#example5").DataTable({
			"responsive": true,
			"scrollX": true,  // Enable horizontal scrolling
			"lengthChange": false,
			"autoWidth": false,
			"buttons" : [
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' }
			]
		});
		table5.buttons().container().appendTo('#example5_wrapper .col-md-6:eq(0)');

		var table6 = $("#example6").DataTable({
			"responsive": true,
			"scrollX": true,  // Enable horizontal scrolling
			"lengthChange": false,
			"autoWidth": false,
			"buttons" : [
				{ extend: 'csv', className: 'btn btn-success' },
				{ extend: 'excel', className: 'btn btn-success' },
				{ extend: 'pdf', className: 'btn btn-success' }
			]
		});
		table6.buttons().container().appendTo('#example6_wrapper .col-md-6:eq(0)');

		var table7 = $("#example7").DataTable({
			"responsive": true,
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
		table7.buttons().container().appendTo('#example7_wrapper .col-md-6:eq(0)');

	});
</script>
<!-- Include Bootstrap JS (if not yet included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Initialize tooltips -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
  });
</script>



</body>

</html>