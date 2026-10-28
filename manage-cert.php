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

    $cert_no = uniqid();


    $sql = "SELECT pc.id, pc.files FROM project p JOIN project_cert pc ON p.id=pc.project_id WHERE p.id='$id' ORDER BY pc.id DESC LIMIT 1";
//echo $sql;

    $result = $db_handle->runBaseQuery($sql);

    if (!empty($result)){

        foreach ($result as $key => $value) {
            $file = $result[$key]["files"];
        }

    }else{
    //echo "error";
        $util->redirect("./admin/dashboard.php?tab=tab5&fail=loa_not_found");
    }
//$db_handle->close();
} //end of isset get
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

            <?php
            if (isset($msg_error)) {
                ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4><i class="icon fa fa-ban"></i> Failed!</h4>
                    <?php echo $msg_error;?>
                </div> 
                <?php
            }
            ?>

            <?php
            if (isset($msg_success)) {
                ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4><i class="icon fa fa-check"></i> Success!</h4>
                    <?php echo $msg_success;?>
                </div> 
                <?php
            }
            ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header with-border">
                            <h3 class="card-title"><b>View LOA</b></h3>
                        </div>
                        <div class="card-body">

                            <div class="row">

                                <a href="./admin/dashboard.php?tab=tab5" class="btn btn-warning mb-3 mr-3">Dashboard</a>

                                <a href="./generate-cert.php?id=<?php echo $id;?>" class="btn btn-info mb-3 mr-3">Regenerate Cert</a>
                                

                                <p>This is the latest certificate generated which may have been received by the participant. If you find abnormalities or error about this LOA you can regenerate again but make sure all the information are correct first.</p>
                                <object data="<?php echo '/download/cert/'.$file;?>" type="application/pdf" style="width:100%;height:1200px;">alt : <a href=""><?php echo $file;?></a></object>

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
</body>
</html>
