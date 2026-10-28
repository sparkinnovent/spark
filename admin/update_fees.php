<?php
session_start();
require_once './Usersession.php';
require_once '../Util.php';
require_once '../Dbcontroller.php';

$user = new Usersession();
$util = new Util();
$db_handle = new Dbcontroller(); 

$login = $user->getSession();
if (!$login) {
    $util->gotohome();
}

$fees_id = $_GET["id"];
$sql = "SELECT * FROM payment_rate WHERE id='$fees_id'";
$result = $db_handle->run($sql);

while($row_new = mysqli_fetch_assoc($result)){

    $name = $row_new["name"];
    $category = $row_new["category"];
    $date_end = $row_new["date_end"];
    $currency = $row_new["currency"];
    $rate = $row_new["rate"];
    $url_link = $row_new["link"]?? null;
    $remark = $row_new["remark"];

}

//update project
if(isset($_POST["update"])){

    $id = $db_handle->clean($_POST["id"]);
    $name = $db_handle->clean($_POST["name"]);
    $category = $db_handle->clean($_POST["category"]);
    $date_end = $db_handle->clean($_POST["date_end"]);
    $date_end = strtotime($date_end);
    $date_end = date('Y-m-d', $date_end);
    $currency = $db_handle->clean($_POST["currency"]);
    $rate = $db_handle->clean($_POST["rate"]);
    $url_link = $db_handle->clean($_POST["url_link"]);
    $remark = $db_handle->clean($_POST["remark"]);
    

    $sql = "UPDATE payment_rate SET name='$name', category='$category', date_end = '$date_end', currency = '$currency', rate = '$rate', link='$url_link', remark='$remark' WHERE id='$id'";

    //echo $sql;
    $result = $db_handle->run($sql);
    if($result){
        $util->redirect("update_fees.php?id=".$id."&success=update");
    }else{
        $util->redirect("update_fees.php?id=".$id."&fail=update");
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>GDIID | ADMIN</title>
    <?php include './header-style.php'; ?>
</head>
<!-- ADD THE CLASS layout-top-nav TO REMOVE THE SIDEBAR. -->
<body class="hold-transition skin-blue layout-top-nav">
    <div class="wrapper">

        <?php include './nav.php';?>

        <!-- Full Width Column -->
        <div class="content-wrapper">
            <div class="container">
                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Update
                        <small>Fees</small>
                    </h1>
                </section>

                <!-- Main content -->
                <section class="content">
                    <?php
                    if (isset($_GET["failinsert"])) {
                        ?>
                        <div class="callout callout-danger">
                            <h4>Error!</h4>
                            <p>Update failed</p>
                        </div>
                        <?php
                    }
                    ?>

                    <?php
                    if (isset($_GET["success"])) {
                        ?>
                        <div class="callout callout-success">
                            <h4>Success!</h4>
                            <p>Update successful. Press <a href="./fees-rate.php" >here</a> to return.</p>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-default">
                                <div class="card-header with-border">
                                    <h5 class="card-title">Fees Details</h5>
                                    <div class="card-tools float-right">
                                        <a class="btn btn-default" href="./fees-rate.php" >Back</a>
                                    </div>
                                </div>

                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form method="post" action="" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                   <label for="email">Name:</label>
                                                   <input type="text" class="form-control" id="name" name="name" value="<?php echo $name;?>">
                                                   <input type="hidden" class="form-control" id="id" name="id" value="<?php echo $fees_id;?>">
                                               </div>

                                           </div>
                                           <div class="form-group col-md-6">
                                            <div class="form-group">
                                                <label for="category">Category</label>
                                                <select id="category" class="form-control" name="category"> 
                                                    <option>Please Select</option>
                                                    <option value="PUBLIC" <?php if($category == "PUBLIC"){echo "selected";}?> >Public</option>
                                                    <option value="MALAYSIAN" <?php if($category == "MALAYSIAN"){echo "selected";}?> >Malaysian</option>
                                                    <option value="INTERNATIONAL" <?php if($category == "INTERNATIONAL"){echo "selected";}?> >International</option>
                                                    <option value="UITMCTKT" <?php if($category == "UITMCTKT"){echo "selected";}?> >Uitmctkt</option>
                                                </select>


                                            </div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <div class="form-group">
                                                <label for="date_end">Date End</label>
                                                <input type="date" class="form-control" id="org" name="date_end" value="<?php echo $date_end;?>" />
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="currency">Currency:</label>
                                                <input type="text" class="form-control" id="currency" name="currency" value="<?php echo $currency;?>">
                                                <select name="currency" class="form-control" id="currency" >
                                                  <option>Please Select</option>
                                                  <option value="MYR" <?php if($currency == "MYR"){echo "selected";}?> >Malaysian Ringgit (MYR)</option>
                                                  <option value="SGD" <?php if($currency == "SGD"){echo "selected";}?> >Singapore Dollar (SGD)</option>
                                                  <option value="USD" <?php if($currency == "USD"){echo "selected";}?> >US Dollar (USD)</option>
                                                  <option value="AUD" <?php if($currency == "AUD"){echo "selected";}?> >Australian Dollar (AUD)</option>
                                                  <option value="IDR" <?php if($currency == "IDR"){echo "selected";}?> >Indonesia Rupee (IDR)</option>
                                                  <option value="BND" <?php if($currency == "BND"){echo "selected";}?> >Brunei Dollar (BND)</option>
                                                  <option value="THB" <?php if($currency == "THB"){echo "selected";}?> >Thailand Baht (THB)</option>
                                              </select>   
                                          </div>
                                      </div>
                                      <div class="col-md-3">
                                         <div class="form-group">
                                            <label for="rate">Rate:</label>
                                            <input type="text" class="form-control" id="rate" name="rate" value="<?php echo $rate;?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                         <div class="form-group">
                                            <label for="rate">URL:</label>
                                            <input type="text" class="form-control" id="url_link" name="url_link" value="<?php echo $url_link;?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="remark">Remark</label>
                                            <textarea class="form-control" id="remark" name="remark"><?php echo $remark;?></textarea>
                                        </div>
                                    </div>
                                </div>      
                                <button type="submit" name="update" class="btn btn-default">Update</button>
                            </form>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.row -->
            </div>
        </section>
    </div>
</div>
<!-- /.content -->
</div>
<!-- /.container -->
<!-- /.content-wrapper -->
<footer class="main-footer">
    <div class="container">
        <div class="pull-right hidden-xs">
            <b>Version</b> 2.4.0
        </div>
        <strong>Copyright &copy; 2014-2016 <a href="https://adminlte.io">Almsaeed Studio</a>.</strong> All rights
        reserved.
    </div>
    <!-- /.container -->
</footer>
</div>
<!-- ./wrapper -->




<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>

<!-- DataTables  & Plugins -->
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../plugins/jszip/jszip.min.js"></script>
<script src="../plugins/pdfmake/pdfmake.min.js"></script>
<script src="../plugins/pdfmake/vfs_fonts.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script>
    $(document).ready(function () {
        $("button#submit").click(function () {
            $.ajax({
                type: "POST",
                url: "event.php",
                data: $('form.feedback').serialize(),
                success: function (message) {
                    $("#feedback").html(message)
                    $("#feedback-modal").modal('hide');
                },
                error: function () {
                    alert("Error");
                }
            });
        });
    });

    $(function () {
        $('#example1').DataTable()
        $('#example2').DataTable({
            'paging': true,
            'lengthChange': false,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false
        })
    })
</script>
</body>
</html>
