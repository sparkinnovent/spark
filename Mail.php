<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';
require_once 'Dbcontroller.php';
//require_once './admin/Usersession.php';

// SMTP settings. Override these in config.php (created by the installer);
// defaults below are placeholders for local development.
if (!defined('SMTP_HOST'))      define('SMTP_HOST', 'mail.example.com');
if (!defined('SMTP_USER'))      define('SMTP_USER', 'no_reply@example.com');
if (!defined('SMTP_PASS'))      define('SMTP_PASS', '');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Conference Secretariat');

/**
 * creted: 27/2/2021
 */
class Mail
{
	private $mail;
	private $db_handle;
	//private $user;
	
	function __construct()
	{
		$this->mail = new PHPMailer(true);
		$this->db_handle = new Dbcontroller();

	}

	public function sendNotify($title, $message, $email){

		try {
            //Server settings
			$this->mail->isSMTP();
            //$mail->SMTPDebug = 2;                                     
			$this->mail->Host = SMTP_HOST;
			$this->mail->SMTPAuth = true;                               
			$this->mail->Username = SMTP_USER;     
			$this->mail->Password = SMTP_PASS;                    
			$this->mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
					'allow_self_signed' => false
				)
			);                         
			$this->mail->SMTPSecure = 'ssl';                           
			$this->mail->Port = 465;                                   

			$this->mail->setFrom(SMTP_USER, SMTP_FROM_NAME);

            //Recipients
			$this->mail->addAddress($email);              
			$this->mail->addReplyTo(SMTP_USER);

            //Content
			$this->mail->isHTML(true);                                  
			$this->mail->Subject = $title;
			$this->mail->Body = $message;

			$this->mail->addBcc("secretariatgdiid@uitm.edu.my");

			$this->mail->send();
            //echo "<script>alert('email sent!');</script>";

			return true;

		} 
		catch (Exception $e) {
			$error_mail = 'Message could not be sent. Mailer Error: '.$this->mail->ErrorInfo;
            //header('location: signup.php');
			return $error_mail;
		}   
	}

	public function sendNotify_w_file($title, $message, $email, $file, $path){

		try {
            //Server settings
			$this->mail->isSMTP();
            //$this->mail->SMTPDebug = 2;                                     
			$this->mail->Host = SMTP_HOST;
			$this->mail->SMTPAuth = true;                               
			$this->mail->Username = SMTP_USER;     
			$this->mail->Password = SMTP_PASS;                    
			$this->mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
					'allow_self_signed' => false
				)
			);                         
			$this->mail->SMTPSecure = 'ssl';                           
			$this->mail->Port = 465;                                   

			$this->mail->setFrom(SMTP_USER, SMTP_FROM_NAME);

            //Recipients
			$this->mail->addAddress($email);              
			$this->mail->addReplyTo(SMTP_USER);

            //Content
			$this->mail->isHTML(true);                                  
			$this->mail->Subject = $title;
			$this->mail->Body = $message;
			$this->mail->addAttachment($path.$file);

			$this->mail->addBcc("secretariatgdiid@uitm.edu.my");

			$this->mail->send();
            //echo "<script>alert('email sent!');</script>";

			return true;

		} 
		catch (Exception $e) {
			$error_mail = 'Message could not be sent. Mailer Error: '.$this->mail->ErrorInfo;
            //header('location: signup.php');
			return $error_mail;
		}   
	}

	public function sendNotify_w_multiple_file($title, $message, $email, $path_file){

		try {
            //Server settings
			$this->mail->isSMTP();
            //$this->mail->SMTPDebug = 2;                                     
			$this->mail->Host = SMTP_HOST;
			$this->mail->SMTPAuth = true;                               
			$this->mail->Username = SMTP_USER;     
			$this->mail->Password = SMTP_PASS;                    
			$this->mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
					'allow_self_signed' => false
				)
			);                         
			$this->mail->SMTPSecure = 'ssl';                           
			$this->mail->Port = 465;                                   

			$this->mail->setFrom(SMTP_USER, SMTP_FROM_NAME);

            //Recipients
			$this->mail->addAddress($email);              
			$this->mail->addReplyTo(SMTP_USER);

            //Content
			$this->mail->isHTML(true);                                  
			$this->mail->Subject = $title;
			$this->mail->Body = $message;

			//Recipients
            foreach ($path_file as $key) {
            $this->mail->addAttachment($key);
            }

			$this->mail->addBcc("urusetiagdiid2021@uitm.edu.my");

			$this->mail->send();
            //echo "<script>alert('email sent!');</script>";
			$this->mail->clearAttachments();
			return true;

		} 
		catch (Exception $e) {
			$error_mail = 'Message could not be sent. Mailer Error: '.$this->mail->ErrorInfo;
            //header('location: signup.php');
			return $error_mail;
		}   
	}


	public function multiple_sendNotify_w_file($title, $message, $email, $file, $path){

		try {
            //Server settings
			$this->mail->isSMTP();
            //$this->mail->SMTPDebug = 2;                                     
			$this->mail->Host = SMTP_HOST;
			$this->mail->SMTPAuth = true;                               
			$this->mail->Username = SMTP_USER;     
			$this->mail->Password = SMTP_PASS;                    
			$this->mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
					'allow_self_signed' => false
				)
			);                         
			$this->mail->SMTPSecure = 'ssl';                           
			$this->mail->Port = 465;                                   

			$this->mail->setFrom(SMTP_USER, SMTP_FROM_NAME);

            //Recipients
            foreach ($email as $key) {
            $this->mail->addAddress($key);
            }              
			$this->mail->addReplyTo(SMTP_USER);

            //Content
			$this->mail->isHTML(true);                                  
			$this->mail->Subject = $title;
			$this->mail->Body = $message;
			$this->mail->addAttachment($path.$file);

			$this->mail->addBcc("secretariatgdiid@uitm.edu.my");

			$this->mail->send();
            //echo "<script>alert('email sent!');</script>";
			$this->mail->clearAddresses();
			//$mail->clearAttachments();
            //}
			
			return true;

		} 
		catch (Exception $e) {
			$error_mail = 'Message could not be sent. Mailer Error: '.$this->mail->ErrorInfo;
            //header('location: signup.php');
			return $error_mail;
		}   
	}

	public function insertlogmail($email, $title, $message, $sender_id, $process){

		//$this->user = new Usersession();

		/*
		 * FIX — $email and $process were interpolated raw. Every other field in
		 * this class at least went through clean(); these two did not, so a
		 * crafted recipient address was an injection point into `mailing`.
		 * Bound parameters now.
		 */
		$link = $this->db_handle->link();
		if (!$link instanceof mysqli) {
			return false;
		}
		$stmt = $link->prepare(
			"INSERT INTO mailing (receiver_email, title, message, sender_id, process) VALUES (?,?,?,?,?)"
		);
		if (!$stmt instanceof mysqli_stmt) {
			error_log('insertlogmail prepare failed: ' . $link->error);
			return false;
		}
		$senderId = $sender_id === null ? '' : (string)$sender_id;
		$stmt->bind_param(
			'sssss',
			$email,
			$title,
			$message,
			$senderId,
			$process
		);
		$ok = $stmt->execute();
		if (!$ok) {
			error_log('insertlogmail execute failed: ' . $stmt->error);
		}
		$stmt->close();
		return (bool)$ok;
	}

	public function notify_unpaid($conf_id, $email){

			$message = '<p>Dear Prof./Assoc. Prof/ Dr./Sir/Madam,</p>';
			$message = $message . '<p>'.'Please be informed that the fee payment deadline is on <b>1st March 2021</b> for your proceeding to be published in the conference e-proceeding.'.'</p>';
			$message = $message . '<p>'.'Registration by Malaysia participants is payable using the MYR (Malaysia Ringgit) currency through Local Order (LO)/ Banker’s Cheque/ Electronic Fund Transfer and registration by international participants, payment to be made in USD (US Dollar) in the form of Banker’s Cheque/ Telegraphic Transfer.'.'</p>';
			$message = $message . '<p>'.'Payment should be payable to:<br>
			Beneficiary Name : UNIVERSITI TEKNOLOGI MARA (UITM)<br>
			Bank Name : Bank Islam Malaysia Berhad, Dungun Branch, Terengganu, Malaysia <br>
			Account No : 1304 4010 0000 11<br>
			Swift Route Code : BIMBMYKL
			'.'</p>';
			$message = $message . '<p>'.'Kindly quote your paper ID (<b>'.$conf_id.'</b>) as a payment reference.   Please <b>upload the proof of payment</b> at <a href="https://temic-uitm.com">https://temic-uitm.com</a> <b>on or before the deadline (1st March 2021).</b> Please be informed that the organisers reserve the right to reject the participation of any application that does not meet stipulated payment requirements.'.'</p>';
			$message = $message . '<p>'.'Please note that all participants are obliged to pay the registration fees according to the conference policy. Those who cancel the participation after the registration date must pay the whole amount of the invoiced fee and kindly note that it is non-refundable.'.'</p>';
			$message = $message . '<p>'.'Thank you.<br>
			Best Regards,<br>
			Secretariat of TeMIC2021
			'.'</p>';


			$notify_email = $this->sendNotify("TEMIC 2021 - Payment Reminder", $message, $email);

			if ($notify_email) {
				$log = $this->insertlogmail($email, "TEMIC 2021 - Payment Reminder", $message, "1", "notify_unpaid");
				if ($log) {
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		
	}

	public function notify_receipt($id, $title, $email, $receipt_file){

			$message = '<p>Dear Prof./Assoc. Prof/ Dr./Sir/Madam,</p>';
            $message = $message. "<p>Conference ID: <b>".$id."</b></p>";
            $message = $message. "<p>Title: <b>".$title."</b></p>";
            $message = $message. "<p>Thank you for your commitment for TEMIC 2021. <br>Attached is the payment receipt for your reference.</p>";
            $message = $message. "<p>Thank you.</p>";         
			$message = $message . '<p>'.'Thank you.<br>Best Regards,Secretariat of TeMIC2021'.'</p>';
			$message = $message. "<p>***This is an automatically generated email, please do not reply directly.</p>";

			$path_file = './upload/receipt/';

			$notify_email = $this->sendNotify_w_file("TEMIC 2021 - Payment Receipt", $message, $email, $receipt_file, $path_file);

			if ($notify_email) {
				$log = $this->insertlogmail($email, "TEMIC 2021 - Payment Receipt", $message, "1", "send_receipt");
				if ($log) {
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		
	}


}