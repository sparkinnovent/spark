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

    $id = $db_handle->clean($_GET["id"]);
    $email = $db_handle->clean($_GET["mail"]);

    $cert_no = uniqid();


    $sql = "SELECT pa.id, pa.file, p.uitmctkt FROM project p JOIN project_loa pa ON p.id=pa.project_id WHERE p.id='$id' AND p.email='$email' ORDER BY pa.id DESC LIMIT 1";
//echo $sql;

    $result = $db_handle->runBaseQuery($sql);

    if (!empty($result)){

        foreach ($result as $key => $value) {
            $file = $result[$key]["file"];
            $uitmkt = $result[$key]["uitmctkt"];
        }

    }else{
    //echo "error";
        Notify::set("LOA not found. Please generate first. ","warning");
        $util->redirect("./admin/participant-loa.php");
    }
} //end of isset get
?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>GDIID | ADMIN</title>

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
                            <h3 class="card-title"><b>View LOA</b></h3>
                        </div>
                        <div class="card-body">

                            <div class="row">

                                <a href="./admin/participant-loa.php" class="btn btn-info mb-3 mr-3">Dashboard</a>
                                <?php
                                if (!$uitmkt) {
                                    ?>
                                    <a href="./send-loa-pub.php?mail=<?php echo $email;?>&id=<?php echo $id;?>" class="btn btn-info mb-3 mr-3">Regenerate LOA</a>
                                <?php
                            }else{
                                ?>
                                <a href="./send-loa-kt.php?mail=<?php echo $email;?>&id=<?php echo $id;?>" class="btn btn-info mb-3 mr-3">Regenerate LOA</a>
                                <?php
                            }
                            ?>

                            <p>This is the last LOA generated which may have been received by the participant. If you find abnormalities or error about this LOA you can regenerate again but make sure all the information are correct first.</p>
                            <object data="<?php echo '/download/loa/'.$file;?>" type="application/pdf" style="width:100%;height:1200px;">alt : <a href=""><?php echo $file;?></a></object>

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
