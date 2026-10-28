<?php
// require_once './prereg/tcpdf.php';
require_once './Dbcontroller.php';
require_once './admin/Usersession.php';
require_once './Util.php';
require_once './Mail.php';

require './vendor/autoload.php';

$db_handle = new DBController();
$util = new Util();


if (isset($_GET["mail"])) {

    $id = $db_handle->clean($_GET["id"]);
    $email = $db_handle->clean($_GET["mail"]);

    $cert_no = uniqid();


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
                $img_file = './dist/img/letterheadPUTIH.png';
                $this->Image($img_file, 0, 0, 210, '', 'PNG', '', 'T', false, 200, '', false, false, 1, false, false, false);
            }

            public function Footer(){

                $img_file_footer = './dist/img/footerSuratPutih.png';
                $this->Image($img_file_footer, 0, 283, 210, '', '', '', '', false, 200, '', false, false, 0);
            }
        }



// create new PDF document

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
        $pdf->SetCreator('GDIID 2023');

        $pdf->SetAuthor('GDIID 2023');

        $pdf->SetTitle('GDIID 2023 - Letter Of Acceptance');

        $pdf->SetSubject('GDIID 2023 - LOA'); 

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
        $ref = "Our Reference: 600-UiTMCTKT (PJI. 5/1)<BR>Date: ".date("d F Y");
        $pdf->writeHTMLCell($w=0, $h=0, $x=120, $y=38, $ref, $border=0, $ln=1, $fill=0, $reseth=false, $align='', $autopadding=true);

        $pdf->SetFont('helvetica', '', 10);

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

        $payment_fees = 'RM 85.00';

        $html_loa_header = '<br><p class="first"><b>LETTER OF ACCEPTANCE</b></p>';

        $message_email = '<p class="first"><b>Dear Author(s),</b></p>';

        $html3 = '<p class="first">We are pleased to inform you that your abstract:</p>';

        $html3 = $html3. '<table>';

        $html3 = $html3. '
        <tr>
        <td width="60">Project ID</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$id.'</b></td>
        </tr>
        <tr>
        <td width="60">Title</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$title.'</b></td>
        </tr>
        <tr>
        <td width="60">Category</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$cat_iid.'</b></td>
        </tr>
        <tr>
        <td width="60">Fees</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$payment_fees.'</b></td>
        </tr>';

        $html3 = $html3. '</table>';

        $html3 = $html3. '<p class="first">has been <b>ACCEPTED</b> to be presented at the <b>3rd Graduate Digital Invention, Innovation and Design (GDIID) Competition 2023</b> which will be held from <b>1st June 2023 to 3rd September 2023.</b></p>';

        $html3 = $html3. '<p class="first">2.   The payment details are available on our website: <a href="https://gdiid.uitm.edu.my" >https://gdiid.uitm.edu.my</a>. The payment can be made via UiTM FinePay at <a target="_blank" href="https://bit.ly/GDIID2023-UITMCTKKT">https://bit.ly/GDIID2023-UITMCTKKT</a>.</p>';

        $html3 = $html3. '<p class="first">3.   Please upload the proof of payment on our <a href="https://gdiid.uitmkt.com">website</a> . Cancellations after the registration deadline require full payment, non-refundable. The secretariat reserves the right to reject non-compliant applications.</p>';

        $html3 = $html3. '<p class="first">4.   Upon payment confirmation, accepted projects will be published on our YouTube channel. Please adhere to submission guidelines and complete the video submission form on our website before <b>17 September 2023.</b></p>';

        $html3 = $html3. '<p class="first">5.   Accepted extended abstracts will be published in the e-proceedings after authors make amendments based on the reviewer\'s comments. Authors must submit revised camera-ready abstracts by <b>15 October 2023</b> with the <b>Turnitin/ Ouriginal report of less than 30 percent (%) similarity index.</b></p>';

        $html_loa_text_1 = $html_style.$html_loa_header.$html3;

        //$pdf->writeHTML($html_loa_text_1, true, false, false, false, '');
        //$pdf->AddPage();

        $html4 = $html_loa_text_1 . '<p class="first">6.   For further enquiries, please do not hesitate to contact us via the attached email address. Congratulations on the acceptance of your project. Please visit our website for the latest updates.</p>';

        $html4 = $html4. '<p>Thank you. </p>';

        $html4 = $html4. '<p>Best Regards,</p>';

        $html4 = $html4. '<p>Secretariat of GDIID2023<br>Universiti Teknologi MARA Cawangan Terengganu<br>Kampus Kuala Terengganu. <br>Website: https://gdiid.uitm.edu.my<br>Email: secretariatgdiid@uitm.edu.my</p>';

        $html4 = $html4. '<p class="last">Computer Generated. Signature is not required.</p>';

        $html_loa_text_2 = $html_style.$html4;

        $pdf->writeHTML($html_loa_text_2, true, false, false, false, '');

        $pdf->setPrintFooter(true);

        $pdf->lastPage();
        $file = 'LOA-'.$id.'-'.$cert_no.'.pdf';
        $pdf->Output(__DIR__ . '/download/loa/'.$file, 'F');

        $sql2 = "INSERT INTO project_loa (project_id, email, file) VALUES ('$id','$email', '$file')";

        $result2 = $db_handle->run($sql2);
        //$message_email = $html2.$html_style.$html3.$html4;
        $message_email = $html2.$html_style.$html4;
        $message_email = base64_encode($message_email);

    }else{
    //echo "error";
        $util->redirect("./admin/dashboard.php?tab=tab1&fail=loa");
    }

//$db_handle->close();
} //end of isset get

if (isset($_POST["send_loa"])) {

    $id = $_POST["id"];
    //$email_list = $_POST["email"];
    $email_list = unserialize(base64_decode($_POST['email']));
    $message_email = $_POST["message_email"];
    $message_email = base64_decode($message_email);
    $file = $_POST["loa_file"];
    $status = $_POST["status"];

    //print_r($email_list);


    if($status == "REGISTER" || $status == "LOA_ERROR" || $status == "LOA"){
        $sql3 = "UPDATE project SET status='LOA' WHERE id='$id'";
//echo $sql3;
        $result3 = $db_handle->run($sql3);

//sending email notification to the user
    //$message_email = $html2.$html_style.$html3.$html4;
        $message_title = "GDIID 2023 - Letter of Acceptance";
        $mailing = new Mail();

        if (isset($email_list)) {
            $sendLoa = $mailing->multiple_sendNotify_w_file($message_title, $message_email, $email_list, $file, __DIR__ .'/download/loa/'); 
        }else{
        //$sendLoa = $mailing->sendNotify_w_file($message_title, $message_email, $email, $file, __DIR__ .'/download/loa/');
        }


//echo $sendLoa;
        if ($sendLoa) {
            $util->redirect("./admin/dashboard.php?tab=tab1&success=notify");
        }else{
            $util->redirect("./admin/dashboard.php?tab=tab1&fail=notify");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>UITMCTKKT | GDIID 2023</title>

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

                            <div class="row">

                                <a href="./admin/dashboard.php?tab=tab1" class="btn btn-warning mb-3 mr-3">Dashboard</a>
                                <a href="./admin/update_project.php?id=<?php echo $id;?>" class="btn btn-info mb-3 mr-3">Update Project</a>

                                <form method="post" action="">
                                    <input type="hidden" name="loa_file" value="<?php echo $file;?>">
                                    <input type="hidden" name="id" value="<?php echo $id;?>">
                                    <?php 
                                    $postvalue = base64_encode(serialize($email_list));
                                    ?>
                                    <input type="hidden" name="email" value="<?php echo $postvalue; ?>">
                                    <input type="hidden" name="message_email" value='<?php echo $message_email; ?>'>
                                    <input type="hidden" name="status" value="<?php echo $status;?>">
                                    <input type="submit" class="btn btn-success mb-3 float-right" name="send_loa" value="Send">
                                </form>


                                <?php
                                $receiver = NULL;
                                foreach ($email_list as $key => $value) {
                                    $receiver = $receiver. $value . ",";
                                }

                                ?>
                                <p>Email Receiver: <?php echo $receiver;?></p>

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

<!--Start of Tawk.to Script-->
<script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/6076cb9d067c2605c0c24c7e/1f380n4ca';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
    })();
</script>
<!--End of Tawk.to Script-->
</body>
</html>
