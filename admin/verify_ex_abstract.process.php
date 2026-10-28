<?php
session_start();
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

if(isset($_GET["accept"])){
	$id = $db_handle->clean($_GET["id"]);
	//$verify_by = $user->getuserid();


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
		$ex_abstract_file = $row1["ex_abstract_file"];
		$turnitin_file = $row1["turnitin_file"];
	}

	$sql = "UPDATE project SET abstract_status='ACCEPT' WHERE id='$id'";

	$result = $db_handle->run($sql);
	if($result){

			$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
			$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
			$message = "";
			while($row = mysqli_fetch_assoc($result_members)){
				$message = $message . "". $row["name"]."<br>";
			}

			$message = $message. "<p>Dear Participants,</p>";

			$message = $message. "<p><b>GDIID 2023 - Extended Abstract Notification</b></p><br>";

			$message = $message. "<p>We are pleased to inform you that the extended abstract of your project has been received.</p>";

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

			$message = $message. "<p>3. For any inquiries, please e-mail us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>";

			$message = $message. "<p>Thank you for your interest to take part in the GDIID 2023.</p>";

			$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";


			$base_path = $_SERVER['DOCUMENT_ROOT'];
			$ex_abstract_file = $base_path.'/upload/abstract/'.$ex_abstract_file;
			$turnitin_file =  $base_path .'/upload/turnitin/'.$turnitin_file;

			echo $ex_abstract_file;
			echo $turnitin_file;

			if (!isset($attachment)) {
				$attachment = array($ex_abstract_file, $turnitin_file);
			}

			print_r($attachment);

			$mail_title = "GDIID 2023 - EXTENDED ABSTRACT NOTIFICATION";
			//$notify = $mail->sendNotify_w_multiple_file($mail_title, $message, $email, $attachment);
		//sendNotify($message, $email);

			echo $notify;

			if ($notify) {
				//$util->redirect("./dashboard.php?success=verify_ex_abstract");
			}else{
			//	$util->redirect("./dashboard.php?fail=verify_ex_abstract");
			}
		//$util->redirect("./dashboard.php?success=verify");
	}else{
	//	$util->redirect("./dashboard.php?fail=verify_ex_abstract");
	}
}

if(isset($_POST["reject"])){
	$id = $db_handle->clean($_GET["id"]);
	$reason = $db_handle->clean($_POST["reason"]);
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
		//$fees = $row1["fees"];
	}

	$sql = "UPDATE project SET status='PRESENT_REJECT' WHERE id='$id'";
	$result = $db_handle->run($sql);
	if($result){

		//find all members in this project
			$sql_members = "SELECT name FROM project_member WHERE project_id='$id'";
			$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
			$message = "";
			while($row = mysqli_fetch_assoc($result_members)){
				$message = $message . "". $row["name"]."<br>";
			}
			$message = $message. "<p>Dear Participants,</p>";

			$message = $message. "<p><b>GDIID 2023 - Extended Abstract Notification</b></p><br>";

			$message = $message. "<p>We are pleased to inform that your extended abstract for</p>";

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
			</table>";

			$message = $message. "<p>has been <b>REMARKED/REJECTED</b> according to some reason.</p>";

			$message = $message. "<p>Reason: ".$reason."</p>";

			$message = $message. "<p>Comment(if available): ".$comment."</p>";

			$message = $message. "<p>3. For any inquiries, please e-mail us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>";

			$message = $message. "<p>Thank you for your interest to take part in the GDIID 2023.</p>";

			$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";


			$mail_title = "GDIID 2023 - EXTENDED ABSTRACT NOTIFICATION";
			$notify = $mail->sendNotify($mail_title, $message, $email);

			if ($notify) {
				$util->redirect("./dashboard.php?success=verify_ex_abstract");
			}else{
				$util->redirect("./dashboard.php?fail=verify_ex_abstract");
			}
	}else{
		$util->redirect("./dashboard.php?fail=verify_ex_abstract");
	}
}



