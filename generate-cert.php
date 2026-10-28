<?php
// require_once './prereg/tcpdf.php';
require_once './Dbcontroller.php';
require_once './admin/Usersession.php';
require_once './Util.php';
require_once './Mail.php';

require './vendor/autoload.php';

$db_handle = new DBController();
$util = new Util();


if (isset($_GET["id"])) {

    $id = $db_handle->clean($_GET["id"]);
    //$email = $db_handle->clean($_GET["mail"]);

    $cert_no = uniqid();


    $sql = "SELECT * FROM project WHERE id='$id'";
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
        // get the current page break margin
                $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
                $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
                $this->SetAutoPageBreak(false, 0);
        // set bacground image
            //$img_file = K_PATH_IMAGES.$cert_image;

                $img_file = './dist/img/cert/SIJIL_PARTICIPANT.png';
                //$img_file = K_PATH_IMAGES.'gdiid-sijil.jpg'; //

                $this->Image($img_file, 0, 0, 210, 297, '', '', '', true, 100, '', false, false, 0);
        // restore auto-page-break status
                $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
                $this->setPageMark();
            }
        }
        
// create new PDF document

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GDIID 2023');
        $pdf->SetTitle('GDIID 2023 - Certificate Participation');
        $pdf->SetSubject('GDIID 2023 - Certificate Participation'); 
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setPrintHeader(true);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 35, 25);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->SetAutoPageBreak(TRUE, 1);
        $scriptBold = TCPDF_FONTS::addTTFfont('font/century-gothic/GOTHICB.TTF', 'TrueTypeUnicode', '', 96);
        $pdf->SetFont($scriptBold , '', 14, '', false);
        // add a page
        $pdf->AddPage();
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        $pdf->MultiCell(175,0,$member_new, 0, 'C', 0, 0, 15, 105, true, 0, true, true, 0);
        $pdf->MultiCell(175, 30, $title, 0,'C', 0, 0, 15, 165, true, 0, false, true, 30, 'M');
        $pdf->lastPage();

        $file = 'cert-part-'.$id.'-'.$cert_no.'.pdf';
        $pdf->Output(__DIR__ . '/download/cert/'.$file, 'F');

//============================================================+
// END OF FILE
//============================================================+

        $sql2 = "INSERT INTO project_cert (project_id, cert_no, files) VALUES ('$id', '$cert_no' , '$file')";
//echo $sql2;
        $result2 = $db_handle->run($sql2);
        $message_email = "hai";
        $message_email = base64_encode($message_email);


    }else{
    //echo "error";
        $util->redirect("./admin/dashboard.php?tab=tab1&fail=loa");
    }

//$db_handle->close();
} //end of isset get

if (isset($_POST["update_cert"])) {

    $pos_name = $_POST["pos_name"];
    $pos_title = $_POST["pos_title"];
    $author_name = $_POST["author_name"];
    $title = $_POST["title"];

    $cert_no = uniqid();

}

if (isset($_POST["send_certificate"])) {

    $id = $_POST["id"]; //project id
    $cert_no = $_POST["cert_no"]; //cert no
    //$email_list = $_POST["email"];
    $email_list = unserialize(base64_decode($_POST['email']));
    $file = $_POST["loa_file"];
    $status = $_POST["status"];
    $title = $_POST["title"];
    $category = $_POST["category"];

    $html = "<p>Dear Prof./Assoc. Prof/ Dr./Sir/Madam,</p>";

    $html = $html . '<p>Project ID: '.$id.'</p>';
    $html = $html . '<p>Title: '.$title.'</p>';
    $html = $html . '<p>Category: '.$category.'</p>';

    $html = $html . '<p>Thank you for joining us in Graduate Digital IID 2021 (GDIID2021). We enclose the certificate of participation herewith and we really appreciate your support and commitment for this program.</p>';

    $html = $html. '<p>For any amendment regarding this certificate, please do not hesitate to contact us through email within a week after receiving this email. Hope to see you again in our future events.</p>';

    $html = $html. '<p>Kind Regards</p>';

    $html = $html. '<p>Certificate Committee</p>';


    $sql3 = "UPDATE project_cert SET status='DELIVERED' WHERE cert_no='$cert_no'";
    $result3 = $db_handle->run($sql3);

    //sending email notification to the user
    $message_title = "GDIID 2021 - Certificate of Participation";
    $mailing = new Mail();

    if (isset($email_list)) {
        $sendLoa = $mailing->multiple_sendNotify_w_file($message_title, $html, $email_list, $file, __DIR__ .'/download/cert/'); 
    }


//echo $sendLoa;
    if ($sendLoa) {
        $util->redirect("./admin/dashboard.php?tab=tab5&success=certificate");
    }else{
        $util->redirect("./admin/dashboard.php?tab=tab5&fail=certificate");
    }
    
}
?>
<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>UITMCTKKT | GDIID 2021</title>

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
                            <h3 class="card-title"><b>Send Certificate</b></h3>
                        </div>
                        <div class="card-body">

                            <div class="row">

                                <a href="./admin/dashboard.php?tab=tab5" class="btn btn-warning mb-3 mr-3">Dashboard</a>
                                <a href="./admin/update_project.php?id=<?php echo $id;?>" class="btn btn-info mb-3 mr-3">Update Project</a>

                                <form method="post" action="">
                                    <input type="hidden" name="loa_file" value="<?php echo $file;?>">
                                    <input type="hidden" name="id" value="<?php echo $id;?>">
                                    <input type="hidden" name="cert_no" value="<?php echo $cert_no;?>">
                                    <input type="hidden" name="category" value="<?php echo $cat_iid;?>">
                                    <input type="hidden" name="title" value="<?php echo $title;?>">
                                    <?php 
                                    $postvalue = base64_encode(serialize($email_list));
                                    ?>
                                    <input type="hidden" name="email" value="<?php echo $postvalue; ?>">                
                                    <input type="hidden" name="status" value="<?php echo $status;?>">
                                    <input type="submit" class="btn btn-success mb-3 float-right" name="send_certificate" value="Send">
                                </form>


                                <?php
                                $receiver = NULL;
                                foreach ($email_list as $key => $value) {
                                    $receiver = $receiver. $value . ",";
                                }

                                ?>
                                <p>Email Receiver: <?php echo $receiver;?></p>

                                <object data="<?php echo '/download/cert/'.$file;?>" type="application/pdf" style="width:100%;height:1200px;">alt : <a href="https://tcpdf.org/files/examples/example_001.pdf"><?php echo $file;?></a></object>

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
