<?php

//get current page

$currentPath = $_SERVER['PHP_SELF'];
if ($currentPath == "/admin/participant.php") {
	$active_page = "participant";
}
else if ($currentPath == "/admin/participant-upload.php"){
	$active_page = "participant-upload";
}
else if ($currentPath == "/admin/participant-loa.php"){
	$active_page = "participant-loa";
}
else if ($currentPath == "/admin/participant-loa.php#sent"){
	$active_page = "participant-loa-sent";
}
else if ($currentPath == "/admin/participant-update.php"){
	$active_page = "participant-update";
}
else if ($currentPath == "/admin/payment.php"){
	$active_page = "payment";
}
else if ($currentPath == "/admin/payment.php#verified"){
	$active_page = "payment-verified";
}
else if ($currentPath == "/admin/fees-rate.php"){
	$active_page = "fees";
}
else if ($currentPath == "/admin/presentation.php"){
	$active_page = "presentation";
}
else if ($currentPath == "/admin/extended-abstract.php"){
	$active_page = "extended-abstract";
}
else if ($currentPath == "/admin/certificate.php"){
	$active_page = "certificate";
}
else if ($currentPath == "/admin/profile-update.php"){
	$active_page = "profile-update";
}
else{
	$active_page = "dashboard";
}

//***********************counter badge*************
// require_once '../Dbcontroller.php';
// $db_handle = new DBController();
//********count total participant
$sql_project_bandge = "SELECT COUNT(id) as number_project FROM project";
$result_project_badge = $db_handle->run($sql_project_bandge);
$row_project = mysqli_fetch_assoc($result_project_badge);
$num_project = $row_project["number_project"];
//count project has not received the LOA
$sql_send_loa = "SELECT COUNT(id) as number_send_loa FROM project WHERE loa_status='NA'";
$result_send_loa = $db_handle->run($sql_send_loa);
$row_send_loa = mysqli_fetch_assoc($result_send_loa);
$num_send_loa = $row_send_loa["number_send_loa"];
// count project has recieved the LOA
$sql_loa_sent_count = "SELECT COUNT(id) as number_loa_sent FROM project WHERE loa_status = 'LOA'";
$result_loa_sent_count = $db_handle->run($sql_loa_sent_count);
$row_loa_sent_count = mysqli_fetch_assoc($result_loa_sent_count);
$num_loa_sent = $row_loa_sent_count["number_loa_sent"];
//count number of admins
$sql_admins = "SELECT COUNT(member_id) as number_admin FROM members";
$result_admins = $db_handle->run($sql_admins);
$row_admins = mysqli_fetch_assoc($result_admins);
$num_number_admin = $row_admins["number_admin"];
// count project has uploaded proof of payment
$sql_upload_payment_count = "SELECT COUNT(id) as number_upload_payment FROM project WHERE payment_status = 'UPLOAD'";
$result_upload_payment_count = $db_handle->run($sql_upload_payment_count);
$row_upload_payment_count = mysqli_fetch_assoc($result_upload_payment_count);
$num_upload_payment = $row_upload_payment_count["number_upload_payment"];
// count project payment was verified
$sql_paid_payment_count = "SELECT COUNT(id) as number_upload_payment FROM project WHERE payment_status = 'PAID'";
$result_paid_payment_count = $db_handle->run($sql_paid_payment_count);
$row_paid_payment_count = mysqli_fetch_assoc($result_paid_payment_count);
$num_paid_payment = $row_paid_payment_count["number_upload_payment"];
// count project has submitted presentation video
$sql_submit_presentation_count = "SELECT COUNT(id) as number_submit_presentation FROM project WHERE presentation_status = 'UPLOAD'";
$result_submit_presentation_count = $db_handle->run($sql_submit_presentation_count);
$row_submit_presentation_count = mysqli_fetch_assoc($result_submit_presentation_count);
$num_submit_presentation = $row_submit_presentation_count["number_submit_presentation"];
// count project has aprroved presentation submission
$sql_verified_presentation_count = "SELECT COUNT(id) as number_verified_presentation FROM project WHERE presentation_status = 'UPLOAD'";
$result_verified_presentation_count = $db_handle->run($sql_verified_presentation_count);
$row_verified_presentation_count = mysqli_fetch_assoc($result_verified_presentation_count);
$num_verified_presentation = $row_verified_presentation_count["number_verified_presentation"];
// count project uploaded extended abstract
$sql_submit_ex_abstract_count = "SELECT COUNT(id) as number_submit_ex_abstract FROM project WHERE abstract_status = 'UPLOAD'";
$result_submit_ex_abstract_count = $db_handle->run($sql_submit_ex_abstract_count);
$row_submit_ex_abstract_count = mysqli_fetch_assoc($result_submit_ex_abstract_count);
$num_submit_ex_abstract = $row_submit_ex_abstract_count["number_submit_ex_abstract"];

?>



<!-- Main Sidebar Container -->

<aside class="main-sidebar elevation-4 sidebar-light-pink">
	<!-- Brand Logo -->
	<a href="#" class="brand-link">
		<img src="../dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
		<span class="brand-text font-weight-light">ADMIN GDIID</span>
	</a>

	<!-- Sidebar -->
	<div class="sidebar">
		<!-- Sidebar user panel (optional) -->
		<div class="user-panel mb-1 d-flex">
			<div class="image mb-2">
				<img src="./dist/<?php echo $user->getPhoto();?>" class="img-circle elevation-2" alt="User Image">
			</div>
			<div class="info">
				<a href="#" class="d-block"><?php echo htmlspecialchars($user->getuserName());?></a>
				<p class="text-sm text-muted mb-0"><strong>Role:</strong> <?php echo htmlspecialchars($user->getuserlevel()); ?></p>
				<p class="text-sm text-muted mb-0"><strong>Last Sign-In:</strong> <?php echo htmlspecialchars($user->getLastSignIn()); ?></p>
				<p class="text-sm text-muted"><strong>Current Sign-In:</strong> <?php echo htmlspecialchars($user->getCurrentSignin()); ?></p>
			</div>
		</div>
		<!-- style lain - tak sesuai untuk mobile -->
		<!-- <div class="user-panel mt-3 pb-3 mb-3 d-flex flex-column align-items-center">
			<div class="image">
				<img src="../dist/img/user2-160x160.jpg" class="elevation-3" alt="User Image" style="width: 150px; height: 100px; object-fit: cover; border-radius: 8px;">
			</div>
			<div class="info mt-2 text-center">
				<a href="#" class="d-block">[PHP user name]</a>
			</div>
		</div> -->
		<!-- Sidebar Menu -->
		<nav class="mt-2">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
               <li class="nav-item">
               	<a href="./secure.php" class="nav-link <?php if ($active_page == "dashboard") {echo 'active';}?>">
               		<i class="nav-icon fas fa-tachometer-alt"></i>
               		<p>Home</p>
               	</a>
               </li>
               <!-- manage participant -->
               <li class="nav-item menu-open">
               	<a href="#" class="nav-link  <?php if ($active_page == "participant" || $active_page == "participant-upload") {echo 'active';}?>">
               		<i class="fas fa-users nav-icon"></i>
               		<p>
               			Participant
               			<i class="fas fa-angle-left right"></i>
               		</p>
               	</a>
               	<ul class="nav nav-treeview">
               		<li class="nav-item">
               			<a href="./participant.php" class="nav-link <?php if ($active_page == "participant") {echo 'active';}?>">
               				<i class="fas fa-user nav-icon"></i>
               				<p>Participant List</p>
               				<span class="badge badge-info right"><?php echo $num_project;?></span>
               			</a>
               		</li>
               		<li class="nav-item">
               			<a href="./participant-upload.php" class="nav-link <?php if ($active_page == "participant-upload") {echo 'active';}?>">
               				<i class="fas fa-upload nav-icon"></i>
               				<p>Upload Participant</p>
               			</a>
               		</li>
               		<li class="nav-item">
               			<a href="./participant-loa.php" class="nav-link <?php if ($active_page == "participant-loa") {echo 'active';}?>">
               				<i class="fas fa-envelope nav-icon"></i>
               				<p>LOA Participant</p>
               				<span class="badge badge-warning right"><?php echo $num_send_loa;?></span>               	
               			</a>
               		</li>
               		<li class="nav-item">
               			<a href="./participant-loa.php#sent" class="nav-link <?php if ($active_page == "participant-loa-sent") {echo 'active';}?>">
               				<i class="fas fa-file-pdf nav-icon"></i>
               				<p>LOA Participant Sent</p>
               				<span class="badge badge-success right"><?php echo $num_loa_sent;?></span>  
               			</a>
               		</li>
               	</ul>
               </li>
               <!-- manage payment -->
               <li class="nav-item menu-open">
               	<a href="#" class="nav-link  <?php if ($active_page == "payment") {echo 'active';}?>">
               		<i class="fas fa-money-bill nav-icon"></i>
               		<p>Payment <i class="fas fa-angle-left right"></i>
               		</p>
               	</a>

               	<ul class="nav nav-treeview">
               		<li class="nav-item">
               			<a href="./payment.php" class="nav-link <?php if ($active_page == "payment") {echo 'active';}?>">
               				<i class="fas fa-hourglass-half nav-icon"></i>
               				<p>Un-Verified Payment</p>
               				<span class="badge badge-warning right"><?php echo $num_upload_payment;?></span>
               			</a>
               		</li>
               		<li class="nav-item">
               			<a href="./payment.php#verified" class="nav-link <?php if ($active_page == "payment-verified") {echo 'active';}?>">
               				<i class="fas fa-money-bill-wave nav-icon"></i>
               				<p>Verified Payment</p>
               				<span class="badge badge-success right"><?php echo $num_paid_payment;?></span>
               			</a>
               		</li>
               	</ul>
               </li>
               <!-- manage presentation -->
               <li class="nav-item menu-open">
               	<a href="#" class="nav-link  <?php if ($active_page == "presentation") {echo 'active';}?>">
               		<i class="fas fa-chalkboard nav-icon"></i>
               		<p>Presentation <i class="fas fa-angle-left right"></i>
               		</p>
               	</a>

               	<ul class="nav nav-treeview">
               		<li class="nav-item">
               			<a href="./presentation.php" class="nav-link <?php if ($active_page == "presentation") {echo 'active';}?>">
               				<i class="fas fa-search nav-icon"></i>
               				<p>Un-Verified Presentation</p>
               				<span class="badge badge-warning right"><?php echo $num_submit_presentation;?></span>
               			</a>
               		</li>
               		<li class="nav-item">
               			<a href="./presentation.php#verified" class="nav-link <?php if ($active_page == "presentation-verified") {echo 'active';}?>">
               				<i class="fas fa-check-circle nav-icon"></i>
               				<p>Verified Presentation</p>
               				<span class="badge badge-success right"><?php echo $num_verified_presentation;?></span>
               			</a>
               		</li>
               	</ul>
               </li>
               <li class="nav-item">
               	<a href="./extended-abstract.php" class="nav-link <?php if ($active_page == "extended-abstract") {echo 'active';}?>">
               		<i class="fas fa-file-alt nav-icon"></i>
               		<p>Extended Abstract</p>
               		<span class="badge badge-success right"><?php echo $num_submit_ex_abstract;?></span>
               	</a>
               </li>
               <li class="nav-item">
               	<a href="./certificate.php" class="nav-link <?php if ($active_page == "certificate") {echo 'active';}?>">
               		<i class="nav-icon fas fa-certificate"></i>
               		<p>Certificate</p>
               	</a>
               </li>

               <li class="nav-header">Profile Setting</li>
               <li class="nav-item">
               	<a href="./profile-update.php" class="nav-link <?php if ($active_page == "profile-update") {echo 'active';}?>">

               		<i class="nav-icon fas fa-cog"></i>
               		<p>Update Profile</p>
               	</a>
               </li>
               <?php
               $userlevel_menu = $user->getuserlevel_id();
               if ($userlevel_menu == 1) {
               	// display the menu
               	?>
               <li class="nav-header">Super Admin</li>
               <li class="nav-item">
               	<a href="./profile-update.php" class="nav-link <?php if ($active_page == "profile-update") {echo 'active';}?>">

               		<i class="nav-icon fas fa-cog"></i>
               		<p>Update Profile</p>
               	</a>
               </li>
               <li class="nav-item">
     			<a href="./fees-rate.php" class="nav-link <?php if ($active_page == "fees") {echo 'active';}?>">
     				<i class="fas fa-credit-card nav-icon"></i>
     				<p>Fees Rate</p>
     			</a>
               </li>
               <li class="nav-item">
               	<a href="./admin-list.php" class="nav-link">
               		<i class="fas fa-tools nav-icon"></i>
               		<p>Admin List</p>
               	</a>
               </li>
               <li class="nav-item">
               	<a href="https://adminlte.io/docs/3.1/" class="nav-link">
               		<i class="nav-icon fas fa-file"></i>
               		<p>Documentation</p>
               	</a>
               </li>
               <?php
               }
               ?>

          </nav>

          <!-- /.sidebar-menu -->

     </div>

     <!-- /.sidebar -->

</aside>