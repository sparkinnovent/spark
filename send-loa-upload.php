<?php
require_once './Dbcontroller.php';
require_once './admin/Usersession.php';
require_once './Util.php';
require_once './Mail.php';

require './vendor/autoload.php';

$db_handle = new DBController();
$util = new Util();


if (isset($_POST["submit_loa"])) {


}

//preload page
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
            $fee_amount = $result[0]["fees"];
            $fee_currency = $result[0]["currency"];
            $status = $result[0]["status"];
            $project_organisation = $result[0]["organisation"];
        }


        $sql_file = "SELECT file FROM ";


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

//update status kat dalam db yang user ni dah download loa

        // $sql2 = "INSERT INTO project_loa (project_id, email, file) VALUES ('$id','$email', '$file')";
//echo $sql2;
        // $result2 = $db_handle->run($sql2);

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
        $message_title = "GDIID 2021 - Letter of Acceptance";
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
                            <h3 class="card-title"><b>Send LOA</b></h3>
                        </div>
                        <div class="card-body">

                            <div class="row">

                                <a href="./admin/dashboard.php?tab=tab1" class="btn btn-warning mb-3 mr-3">Dashboard</a>
                                <a href="./admin/update_project.php?id=<?php echo $id;?>" class="btn btn-info mb-3 mr-3">Update Project</a>

                                <?php 
                                if ($uploaded_loa) {
                                    ?>
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
                                }
                                ?>


                                <?php
                                $receiver = NULL;
                                foreach ($email_list as $key => $value) {
                                    $receiver = $receiver. $value . ",";
                                }

                                ?>
                                <p>Email Receiver: <?php echo $receiver;?></p>
                                <div class="col-12">
                                    <div class="card card-primary">
                                      <div class="card-header">
                                        <h3 class="card-title">Upload LOA</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <!-- form start -->
                                    <form role="form" action="" method="POST" enctype="multipart/form-data">
                                        <div class="card-body">
                                         <div class="form-row">
                                          <div class="form-group col-lg-4">
                                            <label for="exampleInputEmail1">Project ID</label>
                                            <input type="text" class="form-control" id="exampleInputEmail1" value="<?php echo $id;?>" disabled="">
                                        </div>
                                        <div class="form-group col-lg-8">
                                            <label for="title">Title</label>
                                            <input type="text" class="form-control" id="title" placeholder="Enter Title" name="project_title" value="<?php echo $title; ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-lg-6">
                                            <label for="organisation">Organization</label>
                                            <input type="text" class="form-control" id="organisation" name="organisation" placeholder="Enter Organisation" value="<?php echo $project_organisation;?>" >
                                        </div>
                                        <div class="form-group col-lg-3">
                                            <label for="currency">Currency</label>
                                            <input type="text" class="form-control" id="currency" placeholder="Enter Currency" value="USD" name="fee_currency" required="" >
                                        </div>
                                        <div class="form-group col-lg-3">
                                            <label for="fees_amount">Fees</label>
                                            <input type="number" class="form-control" id="fees_amount" placeholder="Fees" name="fees_amount" required="" value="40">
                                        </div>
                                    </div>    
                                    <div class="form-group">
                                        <label for="exampleInputFile">File input LOA</label>
                                        <div class="input-group">
                                          <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="exampleInputFile" name="loa_file">
                                            <label class="custom-file-label" for="exampleInputFile">Choose file</label>
                                        </div>
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="">Upload</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                              <button type="submit" class="btn btn-primary float-right">Upload</button>
                          </div>
                      </form>
                  </div>
              </div>

              <object data="<?php echo '/download/loa/'.$file;?>" type="application/pdf" style="width:100%;height:1200px;">Uploaded LOA will be displayed here</object>

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
