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


if(isset($_POST["accept"])){
    csrf_verify();

	//block access without session
	$login = $user->getSession();
	if (!$login) {
		$util->gotohome();
	}
	//project id
	$id = $db_handle->clean($_POST["id"]);
	//payment_id 
	$payment_id = $db_handle->clean($_POST["payment_id"]);
	//payment_rate
	$payment_rate = $db_handle->clean($_POST["payment_rate"]);
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
		$fees = $row1["fees"];
		$uitmkt = $row1["uitmctkt"];
		$pay_currency = $row1["currency"];
	}

	$sql_Payment_rate = "SELECT * FROM payment_rate WHERE id='$payment_rate'";
	$result_payment_rate = $db_handle->run($sql_Payment_rate);
	while ($row_payment_rate = mysqli_fetch_assoc($result_payment_rate)) {
		
		$payment_currency = $row_payment_rate["currency"];
		$payment_amount = $row_payment_rate["rate"];
	}


	if ($uitmkt) {
		$sql = "UPDATE project SET payment_status='PAID' , fees='$payment_amount' WHERE id='$id'";
	}else{

		$sql = "UPDATE project SET payment_status='PAID', fees='$payment_amount', currency = '$payment_currency' WHERE id='$id'";
	}

	$result = $db_handle->run($sql);
	if($result){

		$sql2 = "UPDATE project_payment SET status='ACCEPT', verified_by='$verify_by', verification_timestamp=now() WHERE id='$payment_id'";
		//echo $sql2;
		$result2 = $db_handle->run($sql2);

		//update project_timeline 2025
		date_default_timezone_set('Asia/Kuala_Lumpur');
		$currentDateTime = date('Y-m-d H:i:s');
		$sql_timeline = "UPDATE project_timeline SET payment_verified='$currentDateTime' WHERE project_id='$id'";
		$result_timeline  = $db_handle->run($sql_timeline);

		if($result2 && $result_timeline){


			$sql_members = "SELECT name FROM project_member WHERE project_id='$id' ORDER BY position ASC";
			$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
			$message = "";
			$project_author = "";
			while($row = mysqli_fetch_assoc($result_members)){
				$project_author = $project_author . "". $row["name"]."<br>";
			}

			$base64 = base64_encode(file_get_contents('../dist/img/logo_2025.jpg'));
			//update 2025 help from chatgpt.
			$message = "<html>
			<head>
			<style>
			body { font-family: Arial, sans-serif; }
			.container { width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9; }
			.header { text-align: center; margin-bottom: 20px; }
			.header img { max-width: 200px; }
			.content { font-size: 14px; color: #333; }
			table { width: 100%; border-collapse: collapse; margin-top: 10px; }
			td { padding: 8px; border-bottom: 1px solid #ddd; }
			.footer { font-size: 12px; color: #666; margin-top: 20px; text-align: center; }
			.footer a { color: #007BFF; text-decoration: none; }
			</style>
			</head>
			<body>
			<div class='container'>
			<div class='header'>
			<img src='data:image/jpg;base64,".$base64."' alt='GDIID 2025'>
			<h2>Payment Confirmation</h2>
			</div>
			<div class='content'>
			<p>{$author}</p>
			<p>Dear Participant,</p>
			<p>We are pleased to confirm that we have received the payment for your project.</p>

			<table>
			<tr>
			<td><strong>Project ID</strong></td>
			<td>:</td>
			<td>".$id."</td>
			</tr>
			<tr>
			<td><strong>Project Title</strong></td>
			<td>:</td>
			<td>".$title."</td>
			</tr>
			<tr>
			<td><strong>Project Category</strong></td>
			<td>:</td>
			<td>".$cat_iid."</td>
			</tr>                    
			<tr>
			<td><strong>Payment Fees</strong></td>
			<td>:</td>
			<td>".$payment_currency." ".$payment_amount."</td>
			</tr>
			</table>

			<p><strong>Next Steps:</strong></p>
			<ol>
			<li>Please upload your extended abstract and presentation video at the following link:</li>
			</ol>
			<p><a href='https://gdiid.uitmkt.com' style='color: #007BFF; text-decoration: none;'>https://gdiid.uitmkt.com</a></p>

			<p>For any inquiries, feel free to contact us at <a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>

			<p>Thank you for your participation in <strong>GDIID 2025!</strong></p>
			</div>

			<div class='footer'>
			<p>*** This is an automated email—please do not reply directly. ***</p>
			</div>
			</div>
			</body>
			</html>";


			$mail_title = "GDIID 2025 - PAYMENT CONFIRMATION";
			$notify = $mail->sendNotify($mail_title, $message, $email);
		//sendNotify($message, $email);

			if ($notify) {
				Notify::set("You have successfully verified the payment. The transaction is now confirmed and the user has been notified.","success");
				
				$util->redirect("./payment.php");
			}else{
				Notify::set("You were unable to verify the payment. Please double-check the transaction details and report ASAP to the developer.","danger");
				$util->redirect("./payment.php");
			}
		}else{

			NOtify::set("Payment verification failed. Unable to update the SQL. Please report ASAP to the developer.","danger");
			$util->redirect("./payment.php");
		}
	}else{
		NOtify::set("Payment verification failed. Unable to Update the SQL. Please report to the developer ASAP.","danger");
		$util->redirect("./payment.php");
	}
}

//process reject payment
if(isset($_POST["reject"])){
    csrf_verify();

	//block access without session
	$login = $user->getSession();
	if (!$login) {
		$util->gotohome();
	}
	$id = $db_handle->clean($_GET["id"]);//project id
	$payment_id = $db_handle->clean($_GET["payment_id"]);
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

	$sql = "UPDATE project SET payment_status='REMARK' WHERE id='$id'";
	$result = $db_handle->run($sql);
	if($result){

		$sql2 = "UPDATE project_payment SET status='REMARK', verified_by='$verify_by', reject_reason='$reason', reject_comment='$comment', verification_timestamp=now() WHERE id='$payment_id'";
		//echo $sql2;
		$result2 = $db_handle->run($sql2);
		if($result2){

		//find all members in this project
			$sql_members = "SELECT name FROM project_member WHERE project_id='$id'";
			$result_members = $db_handle->run($sql_members);

		//$message = '<img src=\"cid:logoimg\" width="100" /><br>';
			$message = "";
			$project_author = "";
			while($row = mysqli_fetch_assoc($result_members)){
				$project_author = $project_author . "". $row["name"]."<br>";
			}

			$message = "<html>
			<head>
			<style>
			body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
			.container { width: 90%; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9; }
			h2 { color: #004085; }
			table { width: 100%; border-collapse: collapse; margin-top: 10px; }
			td { padding: 8px; border-bottom: 1px solid #ddd; }
			.highlight { color: #d9534f; font-weight: bold; }
			.footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
			.btn { background-color: #004085; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; display: inline-block; }
			</style>
			</head>
			<body>
			<div class='container'>
			<h2>GDIID 2025 - Payment Notification</h2>
			<p>{$author}</p>
			<p>Dear Participant,</p>
			<p><b>GDIID 2025 – Payment Notification</b></p>
			<p>We would like to inform you that your payment for the following project has been <span class='highlight'> flagged</span> due to an issue:</p>

			<h3>Project Details:</h3>
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
			</table>

			<p><strong>Reason:</strong> <span class='highlight'>{$reason}</span></p>
			<p><strong>Comments (if applicable):</strong> {$comment}</p>
			<br>
			<p>Should you have any questions or require further clarification, please do not hesitate to contact us at 
			<a href='mailto:secretariatgdiid@uitm.edu.my'>secretariatgdiid@uitm.edu.my</a>.</p>

			<p>Thank you for your interest and participation in <strong>GDIID 2025</strong>.</p>

			<div class='footer'>
			<p>*** This is an automatically generated email. Please do not reply directly. ***</p>
			<p>&copy; 2025 GDIID | Universiti Teknologi MARA</p>
			</div>
			</div>
			</body>
			</html>";

			$mail_title = "GDIID 2025 - PAYMENT NOTIFICATION";
			$notify = $mail->sendNotify($mail_title, $message, $email);

			if ($notify) {
				//$util->redirect("./dashboard.php?tab=tab2&success=verify");
				//update 2025
				Notify::set("You have successfully verified the payment. The transaction is now confirmed and the user has been notified.","success");
				$util->redirect("./payment.php");
			}else{
				NOtify::set("Payment verification failed. Unable to update the SQL. Please report ASAP to the developer.","danger");
				$util->redirect("./payment.php");
			}

		}else{
			NOtify::set("Payment verification failed. Unable to update the SQL. Please report ASAP to the developer.","danger");
			$util->redirect("./payment.php");
		}
	}else{
		NOtify::set("Payment verification failed. Unable to update the SQL. Please report ASAP to the developer.","danger");
		$util->redirect("./payment.php");
	}
}

//update 2025
if (isset($_GET["delete"]) && isset($_GET["file_name"])) {
	$payment_id = $_GET['delete'];
	$file_name = $_GET['file_name']; 
    $file_path = "../upload/payment/" . basename($file_name); // Ensure safe file path

    // Delete the file if it exists
    if (!empty($file_name) && file_exists($file_path)) {
    	if (unlink($file_path)) {
    		Notify::set("Payment proof file deleted successfully.", "success");
    	} else {
    		Notify::set("Failed to delete payment proof file.", "warning");
    	}
    }

    $sql_delete = "DELETE FROM project_payment WHERE id='$payment_id'";
    $result_delete = $db_handle->run($sql_delete);
    if ($result_delete) {
    	Notify::set("Delete payment success.", "success");
    }else{
    	Notify::set("Delete payment failed.", "danger");
    }
    $util->redirect("./payment.php");
}



