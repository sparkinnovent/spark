<?php
session_start();
require_once __DIR__ . '/../security.php';

// Admin console: refuse to render without an authenticated administrator.
require_admin();
require_once './Usersession.php';
require_once './Util.php';

$user = new Usersession();
$util = new Util();

$login = $user->getSession();
if (!$login) {
    $util->gotohome();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>ECERT | E-Certificate UITMCTKKT</title>
        <?php include './include/header-style.php'; ?>
    </head>
    <!-- ADD THE CLASS layout-top-nav TO REMOVE THE SIDEBAR. -->
    <body class="hold-transition skin-blue layout-top-nav">
        <div class="wrapper">

            <?php include './include/navi.php'; ?>

            <!-- Full Width Column -->
            <div class="content-wrapper">
                <div class="container">
                    <!-- Content Header (Page header) -->
                    <section class="content-header">
                        <h1>
                            Member
                            <small>Add New</small>
                        </h1>

                        <ol class="breadcrumb">
                            <li><a href="./"><i class="fa fa-dashboard"></i> Dashboard</a></li>                           
                            <li class="active">Add Member</li>
                        </ol>
                    </section>

                    <!-- Main content -->
                    <section class="content">
                        <?php
                        if (isset($_GET["failinsert"])) {
                            ?>
                            <div class="callout callout-danger">
                                <h4>Error!</h4>
                                <p>Insert new member failed. Possibly duplicate event code</p>
                            </div>
                            <?php
                        }
                        ?>

                        <?php
                        if (isset($_GET["success"])) {
                            ?>
                            <div class="callout callout-success">
                                <h4>Success!</h4>
                                <p>Insert new member successful. Press <a href="./" >here</a> return to dashboard</p>
                            </div>
                            <?php
                        }
                        ?>
                        
                        <?php
                        if (isset($_GET["failexist"])) {
                            ?>
                            <div class="callout callout-warning">
                                <h4>Error!</h4>
                                <p>Insert new member Failed. User name already exist</p>
                            </div>
                            <?php
                        }
                        ?>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="box box-default">
                                    <div class="box-header with-border">
                                        <div class="box-tools pull-right">
                                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                        </div>
                                    </div>

                                    <!-- /.box-header -->
                                    <div class="box-body">
                                        <form method="post" action="admin/membercontroller.php?p=<?php echo basename($_SERVER['PHP_SELF']); ?>">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label> Username</label>
                                                        <div class="input-group">
                                                            <div class="input-group-addon"><i class="fa fa-user"></i> </div>
                                                            <input type="text" class="form-control" name="member_name" />                                                           
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="col-md-3">

                                                    <div class="form-group">
                                                        <label for="exampleInputPassword1">Password</label>
                                                        <div class="input-group">
                                                            <div class="input-group-addon"><i class="fa fa-desktop"></i> </div>
                                                            <INPUT type="text" class="form-control" name="member_password" required />

                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="exampleInputPassword1">Member Role</label>
                                                        <div class="input-group">
                                                            <div class="input-group-addon"><i class="fa fa-asterisk"></i></div>
                                                            <select name="member_role" class="form-control" >
                                                                <option>Please Select</option>
                                                                <option value="2">Admin</option>
                                                                <option value="3">Lecturer</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="exampleInputPassword1">Email Address</label>
                                                        <div class="input-group">
                                                            <div class="input-group-addon"><i class="fa fa-envelope-o"></i></div>
                                                            <INPUT type="email" class="form-control" minlength="4" name="member_email" required />

                                                        </div>
                                                    </div>

                                                </div>
                                            </div>


                                            <div class="box-footer">
                                                <button type="submit" class="btn btn-success pull-right" name="add" value="submit" >
                                                    <i class="fa fa-save"></i> SUBMIT
                                                </button>
                                                <button type="reset" class="btn btn-primary pull-right" style="margin-right: 20px;" name="reset" value="reset">
                                                    <i class="fa fa-refresh"></i> RESET
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!--Add Item Modal -->


                                </div>
                                <!-- /.box -->

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




    <!-- jQuery 3 -->
    <script src="./bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="./bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- SlimScroll -->
    <script src="./bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
    <!-- FastClick -->
    <script src="./bower_components/fastclick/lib/fastclick.js"></script>
    <!-- AdminLTE App -->
    <script src="./dist/js/adminlte.min.js"></script>
    <!-- DataTables -->
    <script src="./bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="./bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="./dist/js/form.js"></script>
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
