<?php
session_start();
require_once __DIR__ . '/security.php';
require_once './Dbcontroller.php';
require_once './admin/Usersession.php';
require_once './Util.php';
require_once './Mail.php';
require_once './Notify.php';

$db_handle = new DBController();
$util = new Util();
$mail = new Mail();
$user = new Usersession();

if(isset($_GET["accept"])){

	$login = $user->getSession();
	if (!$login) {
		$util->gotohome();
	}

	$id = $db_handle->clean($_GET["id"]);
	//$verify_by = $user->getuserid();


	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...
		$email = $row1["email"];
		$title = $row1["title"];
		$title = strtoupper($title);
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}
	$verify_by = $user->getuserid();
	$sql = "UPDATE project SET abstract_status='ACCEPT' WHERE id='$id'";

	$sql_extended_abstract = "UPDATE project_extended_abstract SET status='ACCEPT', verify_by='$verify_by', status_timestamp=NOW() WHERE project_id='$id' AND status='UPLOAD'";


	$result = $db_handle->run($sql);
	$result2 = $db_handle->run($sql_extended_abstract);
	if($result && $result2){

		$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
		$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
		$message = "";
		$message_author = "";
		while($row = mysqli_fetch_assoc($result_members)){
			$message_author = $message_author . "". $row["name"]."<br>";
		}

		$message = "
		<!DOCTYPE html>
		<html>
		<head>
		<style>
		body {
			font-family: Arial, sans-serif;
			background-color: #f4f4f4;
			margin: 0;
			padding: 20px;
		}
		.container {
			max-width: 600px;
			background: #ffffff;
			padding: 20px;
			margin: auto;
			border-radius: 8px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
		}
		.header {
			background-color: #004080;
			color: #ffffff;
			text-align: center;
			padding: 15px;
			font-size: 20px;
			border-radius: 8px 8px 0 0;
		}
		.content {
			padding: 20px;
			font-size: 16px;
			line-height: 1.6;
			color: #333333;
		}
		.table-container {
			width: 100%;
			margin-top: 10px;
			border-collapse: collapse;
		}
		.table-container td {
			padding: 8px;
			border-bottom: 1px solid #ddd;
		}
		.table-container tr:last-child td {
			border-bottom: none;
		}
		.table-container td:first-child {
			font-weight: bold;
			color: #004080;
		}
		.footer {
			background-color: #f4f4f4;
			padding: 15px;
			text-align: center;
			font-size: 14px;
			color: #666666;
			border-radius: 0 0 8px 8px;
		}
		a {
			color: #004080;
			text-decoration: none;
		}
		</style>
		</head>
		<body>

		<div class='container'>
		<div class='header'>
		GDIID 2025 - Submission of Extended Abstract
		</div>

		<div class='content'>
		<p>{$message_author}</p>
		<p>Dear Participants,</p>
		<p>We are pleased to inform you that the extended abstract of your project has been received.</p>
		<table class='table-container'>
		<tr>
		<td>Project ID</td>
		<td>:</td>
		<td>{$id}</td>
		</tr>
		<tr>
		<td>Project Title</td>
		<td>:</td>
		<td>{$title}</td>
		</tr>
		<tr>
		<td>Project Category</td>
		<td>:</td>
		<td>{$cat_iid}</td>
		</tr>
		</table>
		<p><strong>Next Steps:</strong></p>
		<ol>
		<li>The manuscript is currently under review.</li>
		<li>The reviewers' comments will be emailed to you once the feedback is received.</li>
		</ol>

		<p>For any inquiries, please email us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>

		<p>Thank you for your interest in participating in <strong>GDIID 2025</strong>.</p>
		</div>

		<div class='footer'>
        *** This is an automatically generated email. Please do not reply directly. ***
		</div>
		</div>

		</body>
		</html>";

		$base_path = $_SERVER['DOCUMENT_ROOT'];
		$ex_abstract_file = __DIR__ .'/upload/abstract/'.$ex_abstract_file;
		$turnitin_file =   __DIR__ .'/upload/turnitin/'.$turnitin_file;

			// echo $ex_abstract_file;
			// echo $turnitin_file;

		if (!isset($attachment)) {
			$attachment = array($ex_abstract_file, $turnitin_file);
		}

		$mail_title = "GDIID 2025 - EXTENDED ABSTRACT NOTIFICATION";
		$notify = $mail->sendNotify_w_multiple_file($mail_title, $message, $email, $attachment);

		// if ($notify) {
		// 	$util->redirect("./admin/dashboard.php?success=verify_ex_abstract&tab=tab4&tab_ex=tab1");
		// }else{
		// 	$util->redirect("./admin/dashboard.php?fail=verify_ex_abstract&tab=tab4&tab_ex=tab1");
		// }

		if ($notify) {
			//sucess
			Notify::set("file verification successful. The author(s) already been emailed.","success");
		}else{
			Notify::set("file verification failed. Please contact developer ASAP.","danger");
		}
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab1");

	}else{
		Notify::set("Unable to find the project info. Please contact developer ASAP.","danger");
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab1");
	}
}

//reject the ex_abstract stage 1
if(isset($_POST["reject"])){
    csrf_verify();
	$id = $db_handle->clean($_GET["id"]);
	$reason = $db_handle->clean($_POST["reason"]);
	$comment = $db_handle->clean($_POST["comment"]);
	// if($comment == ""){
	// 	$comment = "Not Available";
	// }
	$verify_by = $user->getuserid();

	//gather some information ->project
	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...

		$email = $row1["email"];
		$title = $row1["title"];
		$title = strtoupper($title);
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}

	$sql = "UPDATE project SET abstract_status='REJECT' WHERE id='$id'";

	$sql_extended_abstract = "UPDATE project_extended_abstract SET status='REJECT', verify_by='$verify_by', status_timestamp=NOW() WHERE project_id='$id' AND status='UPLOAD'";

	$result = $db_handle->run($sql);
	$result2 = $db_handle->run($sql_extended_abstract);
	if($result && $result2){

		//find all members in this project
		$sql_members = "SELECT name FROM project_member WHERE project_id='$id'";
		$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
		$message = "";
		$message_author = "";
		while($row = mysqli_fetch_assoc($result_members)){
			$message_author = $message_author . "". $row["name"]."<br>";
		}
		
		$message = "
		<!DOCTYPE html>
		<html>
		<head>
		<style>
		body {
			font-family: Arial, sans-serif;
			background-color: #f4f4f4;
			margin: 0;
			padding: 20px;
		}
		.container {
			max-width: 600px;
			background: #ffffff;
			padding: 20px;
			margin: auto;
			border-radius: 8px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
		}
		.header {
			background-color: #800000;
			color: #ffffff;
			text-align: center;
			padding: 15px;
			font-size: 20px;
			font-weight: bold;
			border-radius: 8px 8px 0 0;
		}
		.content {
			padding: 20px;
			font-size: 16px;
			line-height: 1.6;
			color: #333333;
		}
		.alert {
			background-color: #ffdddd;
			color: #b30000;
			padding: 15px;
			border-left: 5px solid #b30000;
			font-weight: bold;
			margin-bottom: 20px;
		}
		.table-container {
			width: 100%;
			margin-top: 10px;
			border-collapse: collapse;
		}
		.table-container td {
			padding: 8px;
			border-bottom: 1px solid #ddd;
		}
		.table-container tr:last-child td {
			border-bottom: none;
		}
		.table-container td:first-child {
			font-weight: bold;
			color: #800000;
		}
		.footer {
			background-color: #f4f4f4;
			padding: 15px;
			text-align: center;
			font-size: 14px;
			color: #666666;
			border-radius: 0 0 8px 8px;
		}
		a {
			color: #800000;
			text-decoration: none;
		}
		</style>
		</head>
		<body>

		<div class='container'>
		<div class='header'>
		GDIID 2025 - Extended Abstract Revisions Required
		</div>

		<div class='content'>
		<p>{$message_author}</p>
		<p>Dear Participant,</p>

		<div class='alert'>
		<p>We regret to inform you that your extended abstract requires amendments before it can be accepted.</p>
		</div>

		<table class='table-container'>
		<tr>
		<td>Project ID</td>
		<td>:</td>
		<td>{$id}</td>
		</tr>
		<tr>
		<td>Project Title</td>
		<td>:</td>
		<td>{$title}</td>
		</tr>
		<tr>
		<td>Project Category</td>
		<td>:</td>
		<td>{$cat_iid}</td>
		</tr>
		</table>

		<p><strong>Reason for Rejection:</strong> {$reason}</p>
		<p><strong>Reviewer’s Comment (if available):</strong> {$comment}</p>

		<p>
		<strong>Required Action:</strong><br>
		Your extended abstract must follow the stipulated GDIID 2025 format and must not exceed a similarity index of 30%. Please make the necessary amendments and <strong>re-upload your manuscript</strong> before it can proceed to the next review stage.
		</p>

		<p>For any inquiries, please contact us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>

		<p>Thank you for your interest in participating in <strong>GDIID 2025</strong>.</p>
		</div>

		<div class='footer'>
        *** This is an automatically generated email. Please do not reply directly. ***
		</div>
		</div>

		</body>
		</html>";



		$base_path = $_SERVER['DOCUMENT_ROOT'];
		$ex_abstract_file = __DIR__ .'/upload/abstract/'.$ex_abstract_file;
		$turnitin_file =   __DIR__ .'/upload/turnitin/'.$turnitin_file;

			// echo $ex_abstract_file;
			// echo $turnitin_file;

		if (!isset($attachment)) {
			$attachment = array($ex_abstract_file, $turnitin_file);
		}

		$mail_title = "GDIID 2025 - EXTENDED ABSTRACT NOTIFICATION";
			//$notify = $mail->sendNotify($mail_title, $message, $email);
		$notify = $mail->sendNotify_w_multiple_file($mail_title, $message, $email, $attachment);

		// if ($notify) {
		// 	$util->redirect("./admin/dashboard.php?success=verify_ex_abstract&tab=tab4&tab_ex=tab1");
		// }else{
		// 	$util->redirect("./admin/dashboard.php?fail=verify_ex_abstract&tab=tab4&tab_ex=tab1");
		// }
		if ($notify) {
			//sucess
			Notify::set("file rejection successful. The author(s) already been emailed.","success");
		}else{
			Notify::set("file rejection failed. Please contact developer ASAP.","danger");
		}
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab1");
	}else{
		Notify::set("Unable to find the project info. Please contact developer ASAP.","danger");
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab1");
	}
}


if(isset($_POST["submit_review"])){
    csrf_verify();
	$id = $db_handle->clean($_POST["id"]);
	$review_status = $db_handle->clean($_POST["review_status"]); //accept, review
	$review_status = strtoupper($review_status);	
	//$verify_by = $user->getuserid();


	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...
		$email = $row1["email"];
		$title = $row1["title"];
		$title = strtoupper($title);
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}

	//masukkan / save file yang dah diupload dalam folder

	$allowed_type = array ( 'application/pdf', 'application/zip', 'application/octet-stream', 'application/x-zip-compressed', 'multipart/x-zip');

	if (in_array($_FILES["review_file"]["type"], $allowed_type)) {


		$review_path = $_FILES['review_file']['name'];
		$review_ext = pathinfo($review_path, PATHINFO_EXTENSION);   

		$review_temp = explode(".", $_FILES["review_file"]["name"]);
		$review_newfilename = $id."-".round(microtime(true)) . '.' . end($review_temp);
		$review_targetPath = './upload//review/' . $review_newfilename;

		if (move_uploaded_file($_FILES['review_file']['tmp_name'], $review_targetPath)) {

			$uploader_id = $user->getuserid();

			$sql_extended_abstract_review = "INSERT INTO project_ex_abstract_review (project_id, review_file, uploaded_by, status) VALUES ('$id','$review_newfilename','$uploader_id', '$review_status')";

			$sql_abstract = "UPDATE project SET abstract_status='$review_status' WHERE id='$id'";
			
			$result3 = $db_handle->run($sql_extended_abstract_review);
			$result2 = $db_handle->run($sql_abstract);


			if($result2 && $result3){

				//send email yoshhhhh

				$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
				$result_members = $db_handle->run($sql_members);

				$message = "";
				$message_author = "";
				while($row = mysqli_fetch_assoc($result_members)){
					$message_author = $message_author . "". $row["name"]."<br>";
				}

				$message = "
				<!DOCTYPE html>
				<html>
				<head>
				<style>
				body {
					font-family: Arial, sans-serif;
					background-color: #f4f4f4;
					margin: 0;
					padding: 20px;
				}
				.container {
					max-width: 600px;
					background: #ffffff;
					padding: 20px;
					margin: auto;
					border-radius: 8px;
					box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
				}
				.header {
					background-color: #004080;
					color: #ffffff;
					text-align: center;
					padding: 15px;
					font-size: 20px;
					font-weight: bold;
					border-radius: 8px 8px 0 0;
				}
				.content {
					padding: 20px;
					font-size: 16px;
					line-height: 1.6;
					color: #333333;
				}
				.table-container {
					width: 100%;
					margin-top: 10px;
					border-collapse: collapse;
				}
				.table-container td {
					padding: 8px;
					border-bottom: 1px solid #ddd;
				}
				.table-container tr:last-child td {
					border-bottom: none;
				}
				.table-container td:first-child {
					font-weight: bold;
					color: #004080;
				}
				.footer {
					background-color: #f4f4f4;
					padding: 15px;
					text-align: center;
					font-size: 14px;
					color: #666666;
					border-radius: 0 0 8px 8px;
				}
				a {
					color: #004080;
					text-decoration: none;
				}
				</style>
				</head>
				<body>

				<div class='container'>
				<div class='header'>
				GDIID 2025 - Reviewer's Feedback
				</div>

				<div class='content'>
				<p>{$message_author}</p>
				<p>Dear Participant,</p>

				<p><strong>Congratulations!</strong> We are pleased to inform you that your extended abstract has been reviewed.</p>

				<table class='table-container'>
				<tr>
				<td>Project ID</td>
				<td>:</td>
				<td>{$id}</td>
				</tr>
				<tr>
				<td>Project Title</td>
				<td>:</td>
				<td>{$title}</td>
				</tr>
				<tr>
				<td>Project Category</td>
				<td>:</td>
				<td>{$cat_iid}</td>
				</tr>
				</table>

				<p>The reviewer's comments have been attached to this email.</p>

				<p>
				<strong>Next Steps:</strong><br>
				1. Please review the attached document carefully. Ensure that all comments are fully addressed while preparing the final manuscript for the camera-ready version and publication.<br>
				2. Corrections must be clearly identified to avoid any risk of rejection.
				</p>

				<p>
				<strong>Submission Deadline:</strong><br>
				Kindly submit your <strong>camera-ready manuscript</strong> by uploading it for publication at 
				<a href='https://gdiid.uitmkt.com'>https://gdiid.uitmkt.com</a> 
				within <strong>7 days</strong> from the date of this email.
				</p>

				<p>For any inquiries, please contact us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>

				<p>Thank you for your interest in participating in <strong>GDIID 2025</strong>.</p>
				</div>

				<div class='footer'>
        *** This is an automatically generated email. Please do not reply directly. ***
				</div>
				</div>

				</body>
				</html>";


				$review_file =   __DIR__ .'/upload/review/'.$review_newfilename;

			// echo $ex_abstract_file;
			// echo $review_file;

				if (!isset($attachment)) {
					$attachment = array($review_file);
				}

				$mail_title = "GDIID 2025 - EXTENDED ABSTRACT NOTIFICATION";
				$notify = $mail->sendNotify_w_multiple_file($mail_title, $message, $email, $attachment);

				if ($notify) {
			//sucess
					Notify::set("Extended Abstract Review successful. The author(s) already been emailed.","success");
				}else{
					Notify::set("Extended Abstract Review failed. Please contact developer ASAP.","danger");
				}
				$util->redirect("./admin/extended-abstract.php?tab_ex=tab2");
			}else{
				Notify::set("Unable to find the project info. Please contact developer ASAP.","danger");
				$util->redirect("./admin/extended-abstract.php?tab_ex=tab2");
			}
		}        
	}else{
		//file bukan pdf
		Notify::set("The file is not PDF file. Please try again.","danger");
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab2");
		//$util->redirect("./admin/dashboard.php?fail=review_file&tab=tab4&tab_ex=tab2");
	}
}

if(isset($_GET["complete_no_review"])){
	$id = $db_handle->clean($_GET["id"]);
	//$verify_by = $user->getuserid();

	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...
		$email = $row1["email"];
		$title = $row1["title"];
		$title = strtoupper($title);
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}
	$verify_by = $user->getuserid();
	$sql = "UPDATE project SET abstract_status='COMPLETE' WHERE id='$id'";

	$sql_extended_abstract = "UPDATE project_extended_abstract SET status='COMPLETE', verify_by='$verify_by', status_timestamp=NOW() WHERE project_id='$id' AND status='ACCEPT'";


	$result = $db_handle->run($sql);
	$result2 = $db_handle->run($sql_extended_abstract);
	if($result && $result2){

		$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
		$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
		$message = "";
		while($row = mysqli_fetch_assoc($result_members)){
			$message = $message . "". $row["name"]."<br>";
		}

		$message = $message. "<p>Dear Participants,</p>";

		$message = $message. "<p><b>GDIID 2025 - Submission of Extended Abstract</b></p>";



		$message = $message. "<table>
		<tr>
		<td>Project ID</td>
		<td>:</td>
		<td>".$id."</td>
		</tr>
		<tr>
		<td>Project Title</td>
		<td>:</td>
		<td>".$title."</td>
		</tr>
		<tr>
		<td>Project Category</td>
		<td>:</td>
		<td>".$cat_iid."</td>
		</tr>		                    
		</table>";

		$message = $message. "<p>Congratulations! We are pleased to inform you that your extended abstract has been reviewed, and accepted.</p>";

		$message = $message. "<p>2. The manuscript will be published in our 3rd series publication (GDIID 2025) and will be available as a digital collection in the National Library of Malaysia. The published manuscript will be available at https://gdiid.uitm.edu.my/</p>";

		$message = $message. "<p>3. For any inquiries, please e-mail us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>";

		$message = $message. "<p>Thank you for your interest to take part in the GDIID 2025.</p>";

		$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";


		$mail_title = "GDIID 2025 - EXTENDED ABSTRACT NOTIFICATION";
		$notify = $mail->sendNotify($mail_title, $message, $email);

		if ($notify) {
			Notify::set("Extended Abstract accept without review successful. The author(s) already been emailed.","success");
		}else{
			Notify::set("Technical error: 101(email) - COmplete no review process.","warning");
		}
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab2");
	}else{
		Notify::set("Technical error: 101 - Complete no review process","danger");
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab2");
	}
}


if(isset($_GET["accept_correction"])){
	$id = $db_handle->clean($_GET["id"]);
	//$verify_by = $user->getuserid();


	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...
		$email = $row1["email"];
		$title = $row1["title"];
		$title = strtoupper($title);
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}
	$verify_by = $user->getuserid();
	$sql = "UPDATE project SET abstract_status='COMPLETE' WHERE id='$id'";

	$sql_extended_abstract = "UPDATE project_extended_abstract SET status='COMPLETE', verify_by='$verify_by', status_timestamp=NOW() WHERE project_id='$id' AND status='ACCEPT'";


	$result = $db_handle->run($sql);
	$result2 = $db_handle->run($sql_extended_abstract);
	if($result && $result2){

		$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
		$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
		$message = "";
		while($row = mysqli_fetch_assoc($result_members)){
			$message = $message . "". $row["name"]."<br>";
		}

		$message = $message. "<p>Dear Participants,</p>";

		$message = $message. "<p><b>GDIID 2025 - Submission of Extended Abstract</b></p>";



		$message = $message. "<table>
		<tr>
		<td>Project ID</td>
		<td>:</td>
		<td>".$id."</td>
		</tr>
		<tr>
		<td>Project Title</td>
		<td>:</td>
		<td>".$title."</td>
		</tr>
		<tr>
		<td>Project Category</td>
		<td>:</td>
		<td>".$cat_iid."</td>
		</tr>		                    
		</table>";

		$message = $message. "<p>Congratulations! We are pleased to inform you that your extended abstract has been reviewed, and accepted.</p>";

		$message = $message. "<p>2. The manuscript will be published in our 2nd series publication (GDIID 2025) and will be available as a digital collection in the National Library of Malaysia. The published manuscript will be available at https://gdiid.uitm.edu.my/</p>";

		$message = $message. "<p>3. For any inquiries, please e-mail us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>";

		$message = $message. "<p>Thank you for your interest to take part in the GDIID 2025.</p>";

		$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";


		$mail_title = "GDIID 2025 - EXTENDED ABSTRACT NOTIFICATION";
		$notify = $mail->sendNotify($mail_title, $message, $email);

		if ($notify) {
			Notify::set("Accept Correction successful. The auther will get notification from email.","success");
		}else{
			Notify::set("Technical Error: 102(email delivery) - Accept Correction","danger");
		}
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab3");
	}else{
		Notify::set("Technical Error: 102 - Accept Correction.","danger");
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab3");
	}
}

if(isset($_POST["submit_reject_correction"])){
    csrf_verify();

	$id = $db_handle->clean($_POST["id"]);
	//$reason = $db_handle->clean($_POST["reason"]);
	$comment = $db_handle->clean($_POST["comment"]);
	if($comment == ""){
		$comment = "Not Available";
	}
	$verify_by = $user->getuserid();

	//gather some information ->project
	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...

		$email = $row1["email"];
		$title = $row1["title"];
		$title = strtoupper($title);
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}

	$sql = "UPDATE project SET abstract_status='CORRECTION_REMARK' WHERE id='$id'";

	//echo $sql;

	$sql_extended_abstract = "UPDATE project_extended_abstract SET status='CORRECTION_REMARK', verify_by='$verify_by', status_timestamp=NOW() WHERE project_id='$id' AND status='CORRECTION'";

	$result = $db_handle->run($sql);
	$result2 = $db_handle->run($sql_extended_abstract);
	if($result && $result2){

		//echo "hai";

		//find all members in this project
		$sql_members = "SELECT name FROM project_member WHERE project_id='$id'";
		$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
		$message = "";
		while($row = mysqli_fetch_assoc($result_members)){
			$message = $message . "". $row["name"]."<br>";
		}
		$message = $message. "<p>Dear Participants,</p>";

		$message = $message. "<p><b>GDIID 2025 - Submission of Extended Abstract</b></p><br>";

		$message = $message. "<table>
		<tr>
		<td>Project ID</td>
		<td>:</td>
		<td>".$id."</td>
		</tr>
		<tr>
		<td>Project Title</td>
		<td>:</td>
		<td>".$title."</td>
		</tr>
		<tr>
		<td>Project Category</td>
		<td>:</td>
		<td>".$cat_iid."</td>
		</tr>
		</table>";

		$message = $message. "<p>We are sorry to inform you that the extended abstract of your project needs some amendments to be fully accepted.</p>";

		//$message = $message. "<p>Reason: ".$reason."</p>";

		$message = $message. "<p>Comment(if available): ".$comment."</p>";

		$message = $message. "2. The extended abstract must use the stipulated format of GDIID2025 and must not exceed more than 30% of the similarity index. Please do some amendments on the manuscript and UPLOAD it again before the reviewing process. ";

		$message = $message. "<p>3. For any inquiries, please e-mail us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>";

		$message = $message. "<p>Thank you for your interest to take part in the GDIID 2025.</p>";

		$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";


		$base_path = $_SERVER['DOCUMENT_ROOT'];
		$ex_abstract_file = __DIR__ .'/upload/abstract/'.$ex_abstract_file;
		$turnitin_file =   __DIR__ .'/upload/turnitin/'.$turnitin_file;

			// echo $ex_abstract_file;
			// echo $turnitin_file;

		if (!isset($attachment)) {
			$attachment = array($ex_abstract_file, $turnitin_file);
		}

		$mail_title = "GDIID 2025 - EXTENDED ABSTRACT NOTIFICATION";
			//$notify = $mail->sendNotify($mail_title, $message, $email);
		$notify = $mail->sendNotify_w_multiple_file($mail_title, $message, $email, $attachment);

		if ($notify) {
			Notify::set("Reject Correction successful. The auther will get notification from email.","success");
		}else{
			Notify::set("Technical error: 103(email delivery) - Reject Correction.","warning");
		}
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab3");
	}else{
		Notify::set("Technical error: 103 - Reject correction.","danger");
		$util->redirect("./admin/extended-abstract.php?tab_ex=tab3");
	}
}
