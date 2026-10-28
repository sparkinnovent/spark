<?php
session_start();
require_once __DIR__ . '/../security.php';
require_once '../Dbcontroller.php';
require_once './Usersession.php';
require_once '../Util.php';
require_once '../Mail.php';
//update 2025
require_once '../Notify.php';

$db_handle = new DBController();
$util = new Util();
$mail = new Mail();
$user = new Usersession();

$login = $user->getSession();
if (!$login) {
	$util->gotohome();
}

if(isset($_POST["video_accept"])){
    csrf_verify();
	$id = $db_handle->clean($_POST["project_id"]);
	$video_link = $db_handle->clean($_POST["youtube_link"]);
	$video_id = $db_handle->clean($_POST["video_id"]);;

	$verify_by = $user->getuserid();


	$sql1 = "SELECT * FROM project WHERE id='$id'";
	$result1 = $db_handle->run($sql1);
	while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...
		$email = $row1["email"];
		$title = $row1["title"];
		$cat_iid = $row1["cat_iid"];
		$cat_iid = $util->getCategory($cat_iid);
		$cat_iid = strtoupper($cat_iid);
		$uitmkt = $row1["uitmctkt"];
	}

	//update 2025
	date_default_timezone_set('Asia/Kuala_Lumpur');
	$currentDateTime = date('Y-m-d H:i:s');
	$sql_video = "UPDATE project_presentation SET verified_by='$verify_by', verified_timestamp='$currentDateTime' WHERE id='$video_id'";
	$result_video = $db_handle->run($sql_video);

	if (!$result_video) {
		Notify::set("Alert! unable to update video submission validation details.","danger");
		// FIX M5 — was "./presentataion.php" (missing the 'o'). That file does not
	// exist, so every failed verification dumped the admin on a 404.
	$util->redirect("./presentation.php");
	}

	$sql_project = "UPDATE project SET presentation_status='ACCEPT', vid_yt_link = '$video_link'  WHERE id='$id'";

	$result_project = $db_handle->run($sql_project);


	//update project_timeline 2025
	date_default_timezone_set('Asia/Kuala_Lumpur');
	$currentDateTime = date('Y-m-d H:i:s');
	$sql_timeline = "UPDATE project_timeline SET presentation_verified='$currentDateTime' WHERE project_id='$id'";
	$result_timeline  = $db_handle->run($sql_timeline);

	if($result_project && $result_timeline){

		$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
		$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
		$message = "";
		while($row = mysqli_fetch_assoc($result_members)){
			$author = $author . "". $row["name"]."<br>";
		}

		$message = "<html>
		<head>
		<style>
		body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
		.container { width: 90%; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9; }
		h2 { color: #004085; }
		table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		td { padding: 8px; border-bottom: 1px solid #ddd; }
		.footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
		.btn { background-color: #004085; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; display: inline-block; }
		</style>
		</head>
		<body>
		<div class='container'>
		<p>{$author}</p>
		<h2>GDIID 2025 - Presentation Video Submission Confirmation</h2>

		<p>Dear Participant,</p>

		<p>We are pleased to inform you that we have successfully received your presentation video submission for GDIID 2025. Below are the details of your submission:</p>

		<table>
		<tr>
		<td><strong>Project ID</strong></td>
		<td>:</td>
		<td>{$id}</td>
		</tr>
		<tr>
		<td><strong>Project Title</strong></td>
		<td>:</td>
		<td>{$title}</td>
		</tr>
		<tr>
		<td><strong>Project Category</strong></td>
		<td>:</td>
		<td>{$cat_iid}</td>
		</tr>		                    
		<tr>
		<td><strong>Presentation Video</strong></td>
		<td>:</td>
		<td><a href='{$video_link}' target='_blank' class='btn'>View Video</a></td>
		</tr>
		</table>

		<p>If you have not yet uploaded your extended abstract, kindly submit it at the following link:</p>

		<p><a href='https://gdiid.uitmkt.com' target='_blank' class='btn'>Submit Extended Abstract</a></p>

		<p>If you require further assistance, please do not hesitate to contact us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>

		<p>Thank you for your participation in GDIID 2025. We look forward to your valuable contribution.</p>

		<div class='footer'>
		<p>*** This is an automatically generated email. Please do not reply directly. ***</p>
		<p>&copy; 2025 GDIID | Universiti Teknologi MARA</p>
		</div>
		</div>
		</body>
		</html>";


		$mail_title = "GDIID 2025 - PRESENTATION VIDEO NOTIFICATION";
		$notify = $mail->sendNotify($mail_title, $message, $email);
		//sendNotify($message, $email);

		if ($notify) {
			Notify::set("Video presentation successfully verified and the participation has been emailed.","success");
		}else{
			Notify::set("Warning! Video presentation successfully verified but unable to notify through email.","alert");
		}
		$util->redirect("./presentation.php");
	}
}

if(isset($_POST["reject"])){
    csrf_verify();
	$id = $db_handle->clean($_GET["id"]);
	$reason = $db_handle->clean($_POST["reason"]);
	$comment = $db_handle->clean($_POST["comment"]);
	//2025
	$video_id = $db_handle->clean($_POST["video_id"]);

	// if($comment == ""){
	// 	$comment = "Not Available";
	// }

	//check siapa yang verify user ni.
	$verify_by = $user->getuserid();
	if (empty($verify_by)) { // Check if the value is empty (NULL, "", or not set)
	Notify::set("User session has expired. Please log in again.", "danger");
	$util->redirect("./login.php");
    exit; // Ensure script stops execution after redirect
}

	//gather some information ->project
$sql1 = "SELECT * FROM project WHERE id='$id'";
$result1 = $db_handle->run($sql1);
while ($row1 = mysqli_fetch_assoc($result1)) {
		# code...
	$email = $row1["email"];
	$title = $row1["title"];
		//$fees = $row1["fees"];
}

	//update project_timeline 2025
	date_default_timezone_set('Asia/Kuala_Lumpur');
	$currentDateTime = date('Y-m-d H:i:s');

	//$sql = "UPDATE project SET status='PRESENT_REJECT' WHERE id='$id'";
	//update 2025
$sql = "UPDATE project SET presentation_status='REMARK' WHERE id='$id'";
$result = $db_handle->run($sql);



$sql_2 = "UPDATE project_presentation SET verified_by='$verify_by', reject_reason='$reason', reject_comment='$comment', verified_timestamp='$currentDateTime' WHERE id='$video_id'";
$result2 = $db_handle->run($sql_2);
// echo $sql;
// echo "<br>";
// echo $sql_2;

if($result && $result2){

		//find all members in this project
	$sql_members = "SELECT name FROM project_member WHERE project_id='$id'";
	$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
	$message = "";
	while($row = mysqli_fetch_assoc($result_members)){
		$message = $message . "". htmlspecialchars($row["name"])."<br>";
	}
	$message = $message. "<p>Dear Participants,</p>";

	$message = $message. "<p><b>GDIID 2025 - Presentation Video Notification</b></p><br>";

	$message = $message. "<p>We are pleased to inform that your presentation video link for</p>";

	$message = $message. "<table>
	<tr>
	<td>Project ID</td>
	<td>:</td>
	<td>" . htmlspecialchars($id) . "</td>
	</tr>
	<tr>
	<td>Project Title</td>
	<td>:</td>
	<td>" . htmlspecialchars($title) . "</td>
	</tr>
	</table>";

	$message = $message. "<p>has been <b>REMARKED/REJECTED</b> according to some reason.</p>";

	$message = $message. "<p>Reason: ". htmlspecialchars($reason) . "</p>";

	if (!empty($comment)) { 
		$message .= "<p>Comment: " . htmlspecialchars($comment) . "</p>";
	}	

	$message = $message. "<p>3. For any inquiries, please e-mail us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>";

	$message = $message. "<p>Thank you for your interest to take part in the GDIID 2025</p>";

	$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";


	$mail_title = "GDIID 2025 - PRESENTATION VIDEO NOTIFICATION";
	$notify = $mail->sendNotify($mail_title, $message, $email);

		// if ($notify) {

		// 	$util->redirect("./dashboard.php?success=verify_presentation&tab=tab3");
		// }else{
		// 	$util->redirect("./dashboard.php?fail=verify_presentation&tab=tab3");
		// }

	if ($notify) {
		Notify::set("Video presentation successfully verified and the participation has been emailed.","success");
	}else{
		Notify::set("Warning! Video presentation successfully verified but unable to notify through email.","alert");
	}
	$util->redirect("./presentation.php");

}else{
		//$util->redirect("./dashboard.php?fail=verify_presentation&tab=tab3");
	Notify::set("Warning! Unable to update the data. Please contact developer ASAP.","alert");
	$util->redirect("./presentation.php");
}
}



