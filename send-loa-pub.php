<?php
// require_once './prereg/tcpdf.php';
require_once './Dbcontroller.php';
require_once './admin/Usersession.php';
require_once './Util.php';
require_once './Mail.php';
require_once './Notify.php';
require './vendor/autoload.php';

$db_handle = new DBController();
$util = new Util();


if (isset($_GET["mail"])) {
    //session_start();
    $user = new Usersession();
    $login = $user->getSession();
    if (!$login) {
      $util->gotohome();
  }


  $id = $db_handle->clean($_GET["id"]);
  $email = $db_handle->clean($_GET["mail"]);

  $cert_no = uniqid();
  //update 2025 - unicode
  $db_handle->setCharset("utf8mb4");
  $sql = "SELECT * FROM project WHERE id='$id' AND email='$email'";
    //echo $sql;

  $result = $db_handle->runBaseQuery($sql);

  if (!empty($result)){

    foreach ($result as $key => $value) {

        $email = $result[0]["email"];
        $title = $result[0]["title"];
        $title = strtoupper($title);
        $cat_iid = $result[0]["cat_iid"];
        $cat_iid = $util->getCategory($cat_iid);
        $cat_iid = strtoupper($cat_iid);
        $fees = $result[0]["fees"];
        $currency = $result[0]["currency"];
        $status = $result[0]["status"];
    }


    $sql2 = "SELECT * FROM project_member WHERE project_id='$id' ORDER BY position ASC";
    $result2 = $db_handle->run($sql2);

    $member_new = "";
    if(!empty($result2)){

        while ($row2 = mysqli_fetch_assoc($result2)) {
            $name = $row2["name"];
            //$member_new = $member_new . "<br>";
            $member_new = $member_new. $name. "<br>";


            $email_project_member = $row2["email"];

            if ($email_project_member != "") {

                $valid_email_project_member = $util->validateEmail($email_project_member);

                if ($valid_email_project_member) {
                    if (!isset($email_list)) {
                        $email_list = array($email_project_member);
                    }else{
                        array_push($email_list, $email_project_member);
                    }
                }
            } 
        }
    }

    class MYPDF extends TCPDF {
    //Page header
        public function Header() {
        // set bacground image
            $img_file = './dist/img/GDIID2025_LOA_Header2.png';
            $this->Image($img_file, 0, 0, 210, '', 'PNG', '', 'T', false, 200, '', false, false, 1, false, false, false);
                //$this->Image($img_file, 0, 0, 210, 297, 'PNG', '', '', false, 300, '', false, false, 0, false, false, true);
        }

        public function Footer(){
            $img_file_footer = './dist/img/GDIID2025_LOA_Footer.png';
            $this->Image($img_file_footer, 0, 273, 210, '', '', '', '', false, 200, '', false, false, 0);
        }
    }



// create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
    $pdf->SetCreator('GDIID 2025');
    $pdf->SetAuthor('GDIID 2025');
    $pdf->SetTitle('GDIID 2025 - Letter Of Acceptance');
    $pdf->SetSubject('GDIID 2025 - LOA'); 
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 049', PDF_HEADER_STRING);
// set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
// set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->setPrintHeader(true);
// set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, 35, 25);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
// set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 5);
// ---------------------------------------------------------
// set font
    $pdf->SetFont('helvetica', '', 9);
// add a page
    $pdf->AddPage();
//$pdf->setXY(70, 75);

    date_default_timezone_set("Asia/Kuala_Lumpur");
    $ref = "Our Reference: 600-UiTMKKT(KPPIM. 5/1)<BR>Date: ".date("d F Y");
    $pdf->writeHTMLCell($w=0, $h=0, $x=120, $y=38, $ref, $border=0, $ln=1, $fill=0, $reseth=false, $align='', $autopadding=true);

    $pdf->SetFont('helvetica', '', 10);
    //$pdf->SetFont('stsongstdlight', '', 10);  // SimSun alternative

        //$html = "<p>ID NO: ".$paper_reg_id."</p>";
    $html = "<p>";
    $html = $html.$member_new;
//$html = "<p><b>".strtoupper($lect_name)."<BR>".strtoupper($lect_jawatan)."</b>";
    $html = $html."</p>";

//$pdf->writeHTML($html);

    $html2 = $html. "<p>Dear Prof./Assoc. Prof/ Dr./Sir/Madam,</p>";
    $pdf->writeHTML($html2, true, false, false, false, '');

//$pdf->setXY(0, 90);
    $html_style = "
    <!-- EXAMPLE OF CSS STYLE -->
    <style>
    p.first{
        text-align:justify;
        text-justify:inter-word;
    }
    p.last{
        font-style: italic;
        font-size: 10px;
        text-align:justify;
        text-justify:inter-word;
    }
    ol.k {
        list-style-type: lower-alpha;
        text-align:justify;
        text-justify:inter-word;
    }
    </style>
    ";

    $payment_fees = '<a target="_blank" href="https://bit.ly/GDIID2023-UITM">UITM PAY</a>';

    $html_loa_header = '<br><p class="first"><b>LETTER OF ACCEPTANCE</b></p>';

    $message_email = '<p class="first"><b>Dear Author(s),</b></p>';

    $html_1 = '<p class="first">We are pleased to inform you that your project has been <b>ACCEPTED</b> to be presented at the <b>4th Graduate Digital Invention, Innovation and Design (GDIID) Competition 2025</b> which will be held from <b>1st February 2025 to 6th May 2025</b></p>';

    $pdf->writeHTML($html_style.$html_loa_header.$html_1, true, false, false, false, '');
    //tukar font yang boleh tulis chinese
    $pdf->SetFont('arialunicodems', '', 10);  // SimSun alternative
    $html_2 = "<p></p>";
    $html_2 = $html_2. '<table>';

    $html_2 = $html_2. '
    <tr>
    <td width="80">Project ID</td>
    <td width="10">:</td>
    <td width="400" ><b>'.$id.'</b></td>
    </tr>
    <tr>
    <td width="80">Title</td>
    <td width="10">:</td>
    <td width="400" ><b>'.$title.'</b></td>
    </tr>
    <tr>
    <td width="80">Category</td>
    <td width="10">:</td>
    <td width="400" ><b>'.$cat_iid.'</b></td>
    </tr>
    <tr>
    <td width="80">Corresponding Email</td>
    <td width="10">:</td>
    <td width="400" ><b>'.$email.'</b></td>
    </tr>';

    $html_2 = $html_2. '</table>';
    //print html_2
    $pdf->writeHTML($html_2, true, false, false, false, '');
    //tukar balik ke font helvetica
    $pdf->SetFont('helvetica', '', 10);

    $html_3 =  '<p class="first">2.   Please submit your proof of payment, video submission and extended abstract (optional) to confirm your participation through the <a href="https://gdiid.uitmkt.com" target="_blank">GDIID2025 system</a>. Kindly refer to the datelines below</p>';

    $html_3 = $html_3. '<table>';

    $html_3 = $html_3. '
    <tr>
    <td width="10"></td>
    <td width="20">a)</td>
    <td width="200">Payment Deadline</td>
    <td width="10">:</td>
    <td width="100">22 May 2025</td>
    </tr>
    <tr>
    <td width="10"></td>
    <td width="20">b)</td>
    <td width="200">Video Submission Deadline</td>
    <td width="10">:</td>
    <td width="100">25 May 2025</td>
    </tr>
    <tr>
    <td width="10"></td>
    <td width="20">c)</td>
    <td width="200">Extended Abstract Submission Deadline</td>
    <td width="10">:</td>
    <td width="100">30 June 2025</td>
    </tr>
    <tr>
    <td width="10"></td>
    <td width="20">d)</td>
    <td width="200">Judging Period</td>
    <td width="10">:</td>
    <td width="100">1 – 15 June 2025</td>
    </tr>';

    $html_3 = $html_3. '</table>';

    $html_3 = $html_3 . '<p class="first">3.   For further enquiries, please do not hesitate to contact us via the attached email address. Congratulations on the acceptance of your project. Please visit our website for the latest updates.</p>';

    $html_3 = $html_3. '<p>Thank you. </p>';

    $html_3 = $html_3. '<p>Best Regards,</p>';

    $html_3 = $html_3. '<p>Secretariat of GDIID2025<br>Universiti Teknologi MARA Cawangan Terengganu<br>Kampus Kuala Terengganu. <br>Website: https://gdiid.uitm.edu.my<br>Email: secretariatgdiid@uitm.edu.my</p>';

    $html_3 = $html_3. '<p class="last">Computer Generated. Signature is not required.</p>';

    //$html_loa_text_2 = $html_style.$html4;

    $pdf->writeHTML($html_3, true, false, false, false, '');

    $pdf->setPrintFooter(true);

    $pdf->lastPage();
    $file = 'LOA-'.$id.'-'.$cert_no.'.pdf';
    $pdf->Output(__DIR__ . '/download/loa/'.$file, 'F');

        //get the admin who generate the LOA
    $admin_id = $user->getID();

    $sql2 = "INSERT INTO project_loa (project_id, email, file, admin_id) VALUES ('$id','$email', '$file', '$admin_id')";
        //echo $sql2;
    $result2 = $db_handle->run($sql2);
        //$message_email = $html2.$html_style.$html3.$html4;
    /*
     * FIX M6 — $html4 was never defined in this scope; error_log recorded
     * "Undefined variable $html4" on every generation. The body is assembled
     * from the parts that actually exist, in the same order the PDF uses.
     */
    $message_email = $html2 . $html_style
        . $html_loa_header
        . $html_1 . $html_2 . $html_3;
    $message_email = base64_encode($message_email);

}else{
    //echo "error";
    Notify::set("Ünable to retrieve the record. Please report to developer ASAP. Thanks.","danger");
        //$util->redirect("./admin/participant-loa.php");
}

//$db_handle->close();
} //end of isset get

if (isset($_POST["send_loa"])) {
    $user = new Usersession();

    $login = $user->getSession();
    if (!$login) {
      $util->gotohome();
  }

  $id = $_POST["id"];
    //$email_list = $_POST["email"];
  $email_list = unserialize(base64_decode($_POST['email']));
  $message_email = $_POST["message_email"];
  $message_email = base64_decode($message_email);
  $file = $_POST["loa_file"];
  $status = $_POST["status"];

    //print_r($email_list);


  if($status == "REGISTER" || $loa_status == "LOA_ERROR" || $loa_status == "LOA"){
    $sql3 = "UPDATE project SET loa_status='LOA' WHERE id='$id'";
//echo $sql3;
    $result3 = $db_handle->run($sql3);

//sending email notification to the user
    //$message_email = $html2.$html_style.$html3.$html4;
    $message_title = "GDIID 2025 - Letter of Acceptance";
    $mailing = new Mail();

    if (isset($email_list)) {
        $sendLoa = $mailing->multiple_sendNotify_w_file($message_title, $message_email, $email_list, $file, __DIR__ .'/download/loa/'); 
    }else{
        //$sendLoa = $mailing->sendNotify_w_file($message_title, $message_email, $email, $file, __DIR__ .'/download/loa/');
    }
    if ($sendLoa) {
        Notify::set("Sending LOA to the participant through email was successful. ","success");
        $util->redirect("./admin/participant-loa.php");
    }else{
       Notify::set("Sending LOA to the participant using email failed. Please report to developer ASAP. ","danger");
       $util->redirect("./admin/participant-loa.php");
   }
}
}
?>
<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>GDIID | ADMIN</title>

    <?php //include './include/header-style.php'; ?>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="./dist/css/adminlte.min.css">

    <link href="./dist/img/uitm-icon.png" rel="icon">

</head>
<body class="layout-top-nav">

    <!-- Preloader -->
    <!-- <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="./dist/img/logoFBIG.png" alt="AdminLTELogo" height="200">
    </div> -->

    <!-- AdminLTE Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="./dist/img/logo_2025.jpg" alt="AdminLTELogo" height="200">
    </div>


    <div class="wrapper">

       <!-- Navbar -->
       <?php include './nav-default.php';?>
       <!-- /.navbar -->

       <!-- Full Width Column -->

       <div class="content-wrapper">
        <div class="container">

            <!-- Content Header (Page header) -->
            <section class="content-header">
              <div class="container-fluid">
                <div class="row mb-2">

                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header with-border">
                            <h3 class="card-title"><b>Send LOA</b></h3>
                        </div>
                        <div class="card-body">

                            <div class="row mt-3">
                                <!-- Navigation Buttons -->
                                <div class="col-md-8">
                                    <div class="btn-group">
                                        <a href="./admin/secure.php?" class="btn btn-warning btn-lg">
                                            <i class="fas fa-home"></i> Home
                                        </a>
                                        <a href="./admin/participant-loa.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-list"></i> LOA List
                                        </a>
                                        <a href="./admin/update_project.php?id=<?php echo $id;?>" class="btn btn-info btn-lg">
                                            <i class="fas fa-edit"></i> Update Project
                                        </a>

                                    </div>
                                </div>

                                <!-- Email Recipients Section -->
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <strong><i class="fas fa-users"></i> Email Recipients</strong>
                                        </div>
                                        <div class="card-body p-2">
                                            <ul class="list-unstyled mb-0">
                                                <?php
                                                $receiver = NULL;
                                                foreach ($email_list as $key => $value) {
                                                    $receiver .= "<li'><i class='fas fa-envelope text-primary'></i> " . htmlspecialchars($value) . "</li><br>";
                                                }
                                                ?>
                                                <?php echo $receiver; ?>
                                            </ul>
                                        </div>
                                        <div class="card-footer text-center">
                                            <form method="post" action="" id="pdfForm">
                                                <input type="hidden" name="loa_file" value="<?php echo $file;?>">
                                                <input type="hidden" name="id" value="<?php echo $id;?>">
                                                <?php 
                                                $postvalue = base64_encode(serialize($email_list));
                                                ?>
                                                <input type="hidden" name="email" value="<?php echo $postvalue; ?>">
                                                <input type="hidden" name="message_email" value='<?php echo $message_email; ?>'>
                                                <input type="hidden" name="status" value="<?php echo $status;?>">
                                                <button type="submit" class="btn btn-success btn-block" name="send_loa">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row">

                                <object data="<?php echo '/download/loa/'.$file;?>" type="application/pdf" style="width:100%;height:1200px;">alt : <a href="https://tcpdf.org/files/examples/example_001.pdf"><?php echo $file;?></a></object>

                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>
            </div>

        </section>

        <!-- /.container -->
    </div>

    <!-- /.content-wrapper -->
</div>

<!-- ./wrapper -->

<!-- jQuery -->
<script src="./plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="./dist/js/adminlte.js"></script>

<script>
    document.getElementById("pdfForm").addEventListener("submit", function() {
        document.querySelector(".preloader").style.display = "flex"; // Show preloader
    });
</script>

<script>
  $(document).ready(function () {
    $('.btn-loa').click(function (e) {
      e.preventDefault();
      var id = $('#id').val();
      var email = $('#email').val();
      $.ajax
      ({
          type: "POST",
          url: "download-loa.php",
          data: { "id": id, "email": email},
          success: function (data) {
            console.log(data);
             //$('#myModal').find('#modalAlert').addClass('alert-success');
            $('#myModal').find('.result').html(data).show; 
             //$('#myModal').find('#modalAlert').removeClass('hidden');

            $('#myModal').modal('show');
        }
    });
  });
});
</script>
</body>
</html>
