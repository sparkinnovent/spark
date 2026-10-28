<?php
session_start();
require_once './Usersession.php';
require_once '../Util.php';
require_once '../Dbcontroller.php';
require_once '../Notify.php';

$user = new Usersession();
$util = new Util();
$db_handle = new Dbcontroller(); 

$login = $user->getSession();
if (!$login) {
    $util->gotohome();
}

$project_id = $_GET["id"];
$sql = "SELECT * FROM project WHERE id='$project_id'";
$result = $db_handle->run($sql);

while($row_new = mysqli_fetch_assoc($result)){

    $email = $row_new["email"];
    $org = $row_new["organisation"];
    $country = $row_new["country"];
    $cat_iid = $row_new["cat_iid"];
    $title = $row_new["title"];

    $contact_no = $row_new["contact_no"];

    //echo $title;

    $status = $row_new["status"];
    $upload_id = $row_new["upload_id"];
    $upload_timestamp = $row_new["upload__timestamp"];
    $uploader_id = $row_new["uploader_id"];
    $fees = $row_new["fees"];
    $currency = $row_new["currency"];

    $status = $row_new["status"];
    $payment_status = $row_new["payment_status"];
    //update 2025
    $loa_status = $row_new["loa_status"];
    $presentation_status = $row_new["presentation_status"];
    $uitmctkt = $row_new["uitmctkt"];
    $student_card_link = $row_new["student_card_link"];
    $abstract_link = $row_new["abstract_link"];

    $presentation_link = $row_new["video_link"];
    $ex_abstract_file = $row_new["ex_abstract_file"];
    $turnitin_file = $row_new["turnitin_file"];

    $abstract_status = $row_new["abstract_status"];
}

//update project
if(isset($_POST["update"])){

    $id = $db_handle->clean($_POST["id"]);
    $email = $db_handle->clean($_POST["email"]);
    $org = $db_handle->clean($_POST["organisation"]);
    $org = strtoupper($org);

    $country = $db_handle->clean($_POST["country"]);
    $contact_no = $db_handle->clean($_POST["contact_no"]);
    $cat_iid = $db_handle->clean($_POST["cat_iid"]);
    $uitmctkt = $db_handle->clean($_POST["uitmctkt"]);
    $title = $db_handle->clean($_POST["title"]);
    $fees = $db_handle->clean($_POST["fees"]);
    $currency = $db_handle->clean($_POST["currency"]);

    $status = $db_handle->clean($_POST["status"]);
    $payment_status = $db_handle->clean($_POST["payment_status"]);
    //update 2025
    $loa_status = $db_handle->clean($_POST["loa_status"]);
    $presentation_status = $db_handle->clean($_POST["presentation_status"]);
    
    $student_card_link = $db_handle->clean($_POST["student_card_link"]);
    $abstract_link = $db_handle->clean($_POST["abstract_link"]);
    $presentation_link = $db_handle->clean($_POST["presentation_link"]);
    //$ex_abstract_file = $db_handle->clean($_POST["ex_abstract_file"]);
    //$turnitin_file = $db_handle->clean($_POST["turnitin_file"]);

    $abstract_status = $db_handle->clean($_POST["abstract_status"]);

    if (is_uploaded_file($_FILES['ex_abstract_file']['tmp_name'])) {

    }


    //$sql = "UPDATE project SET email='$email', organisation='$org', country='$country', contact_no='$contact_no', cat_iid='$cat_iid', title='$title', fees='$fees', currency='$currency', status='$status', payment_status='$payment_status', uitmctkt='$uitmctkt', student_card_link='$student_card_link', abstract_link='$abstract_link', video_link='$presentation_link', abstract_status='$abstract_status' WHERE id='$id'";

    //update 2025 - unicode
    $db_handle->setCharset("utf8mb4");

    $sql = "UPDATE project SET email='$email', organisation='$org', country='$country', contact_no='$contact_no', cat_iid='$cat_iid', title='$title', fees='$fees', currency='$currency', status='$status', payment_status='$payment_status', loa_status = '$loa_status', presentation_status = '$presentation_status' , uitmctkt='$uitmctkt', student_card_link='$student_card_link', abstract_link='$abstract_link', video_link='$presentation_link', abstract_status='$abstract_status' WHERE id='$id'";


    //echo $sql;
    $result = $db_handle->run($sql);
    if($result){
        $util->redirect("update_project.php?id=".$id."&success=update_project");
    }else{
        $util->redirect("update_project.php?id=".$id."&fail=update_project");
    }
}

//update members
if(isset($_POST["update_member"])){

    //project id
    $id = $_GET["id"];

    foreach ($_POST['id'] as $key => $val) {
        $member_id = $db_handle->clean($_POST['id'][$key]);
        $position = $db_handle->clean($_POST['number'][$key]);
        $name = $db_handle->clean($_POST['name'][$key]);
        $email = $db_handle->clean($_POST['email'][$key]);


        $sql = "UPDATE project_member SET position='$position', name='$name',email='$email' WHERE id='$member_id'";
        //echo $sql;
        $result = $db_handle->run($sql);
        if(!$result){
            $util->redirect("update_project.php?id=".$id."&fail=update_member");
        }
    }
    //update success
    $util->redirect("update_project.php?id=".$id."&success=update_member");
}

if(isset($_POST["add_member"])){
    //project id

    $organisation = $db_handle->clean($_POST['organisation']);
    $organisation = strtoupper($organisation);
    $email = $db_handle->clean($_POST['email']);
    $role = $db_handle->clean($_POST['role']);
    $position = $db_handle->clean($_POST['number']);
    $name = $db_handle->clean($_POST['name']);
    $name = strtoupper($name);
    $project_id = $db_handle->clean($_POST['project_id']);
    $upload_id = "add_manual";

    $sql = "INSERT INTO project_member (project_id, position, name, email, organisation, role, upload_id) VALUES ('$project_id','$position','$name','$email','$organisation','$role','$upload_id')";

    echo $sql;
    $result = $db_handle->run($sql);
    if($result){
        //$util->redirect("update_project.php?id=".$project_id."&success=add_member");
    }else{
        $util->redirect("update_project.php?id=".$project_id."&fail=add_member");
    }
}

if(isset($_GET["member_delete"])){

    $project_id = $db_handle->clean($_GET['project_id']);
    $member_id = $db_handle->clean($_GET['id']);
    
    $sql = "DELETE FROM project_member WHERE id='$member_id'";
    $result = $db_handle->run($sql);
    if(!$result){
        $util->redirect("update_project.php?id=".$project_id."&fail=delete_member");
    }
    $util->redirect("update_project.php?id=".$project_id."&success=delete_member");

}

if(isset($_GET["delete"])){

    $file_to_delete = $db_handle->clean($_GET['delete']);
    $project_id = $db_handle->clean($_GET['id']);

    if ($file_to_delete == "abstract_file") {
        $sql = "UPDATE project set ex_abstract_file='' WHERE id='$project_id'";
    }
    if ($file_to_delete == "turnitin_file") {
        $sql = "UPDATE project set turnitin_file='' WHERE id='$project_id'";
    }
    
    $result = $db_handle->run($sql);
    if(!$result){
        $util->redirect("update_project.php?id=".$project_id."&fail=delete_file");
    }
    $util->redirect("update_project.php?id=".$project_id."&success=delete_file");

}


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>GDIID | Update Project</title>
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
                        Update Project
                        <small>ID: <?php echo $project_id;?></small>
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
                            <p>Update successful. Press <a href="./participant.php" >here</a> return to participant list</p>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-default">
                                <div class="card-header with-border">
                                    <h5 class="card-title">Project Details</h5>
                                    <div class="card-tools float-right">
                                        <?php
                                        if (!$uitmctkt) {
                                            ?>
                                            <a href="../send-loa-pub.php?mail=<?php echo $email;?>&id=<?php echo $project_id;?>" class="btn btn-primary mr-3">Regenerate LOA</a>
                                            <?php
                                        }else{
                                            ?>
                                            <a href="../send-loa-pub.php?mail=<?php echo $email;?>&id=<?php echo $project_id;?>" class="btn btn-primary mr-3">Regenerate LOA</a>
                                            <?php
                                        }
                                        ?>


                                        <a class="btn btn-default" href="./participant.php" >Back</a>
                                    </div>
                                </div>

                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form method="post" action="" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                 <label for="email">Email:</label>
                                                 <input type="email" class="form-control" id="email" name="email" value="<?php echo $email;?>">
                                                 <input type="hidden" class="form-control" id="id" name="id" value="<?php echo $project_id;?>">
                                             </div>

                                         </div>
                                         <div class="form-group col-md-6">
                                            <div class="form-group">
                                                <label for="org">Organisation</label>
                                                <input type="text" class="form-control" id="org" name="organisation" value="<?php echo $org;?>" />
                                            </div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <div class="form-group">
                                                <label for="org">Upload ID</label>
                                                <input type="text" class="form-control" id="org" name="upload_id" value="<?php echo $upload_id;?>" />
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="country">Country:</label>
                                                <input type="text" class="form-control" id="country" name="country" value="<?php echo $country;?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                           <div class="form-group">
                                            <label for="contact_no">Contact:</label>
                                            <input type="text" class="form-control" id="contact_no" name="contact_no" value="<?php echo $contact_no;?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="cat_iid">Catergory IID:</label>
                                            <input type="text" class="form-control" id="cat_iid" name="cat_iid" value="<?php echo $cat_iid;?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                      <div class="form-group">
                                        <label for="uitmctkt">UITMKT</label>
                                        <select name="uitmctkt" class="form-control" id="uitmctkt">
                                            <option value=1 <?php if($uitmctkt == 1){echo "selected";}?>>YES</option>
                                            <option value=0 <?php if($uitmctkt == 0){echo "selected";}?>>NO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="col-xs-5">
                                        <label for="title">Title:</label>
                                        <textarea id="title" name="title" rows="3" class="form-control"><?php echo $title;?></textarea>

                                    </div>
                                </div>
                                <div class="col-md-3">
                                  <div class="form-group">
                                    <label for="currency">Currency</label>
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
                                <label for="fees">Fees</label>
                                <input type="text" class="form-control" id="fees" name="fees" value="<?php echo $fees;?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                       <div class="col-md-2">
                          <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control" id="status">
                                <option value="REGISTER" <?php if($status == "REGISTER"){echo "selected";}?>>REGISTER</option>
                                <option value="LOA" <?php if($status == "LOA"){echo "selected";}?>>LOA</option>
                                <option value="LOA_ERROR" <?php if($status == "LOA_ERROR"){echo "selected";}?>>LOA_ERROR</option>
                                <option value="PRESENT" <?php if($status == "PRESENT"){echo "selected";}?>>PRESENT</option>
                                <option value="PRESENT_ACCEPT" <?php if($status == "PRESENT_ACCEPT"){echo "selected";}?>>PRESENT_ACCEPT</option>
                                <option value="PRESENT_REJECT" <?php if($status == "PRESENT_REJECT"){echo "selected";}?>>PRESENT_REJECT</option>
                                <option value="EX_ABSTRACT" <?php if($status == "EX_ABSTRACT"){echo "selected";}?>>EX_ABSTRACT</option>
                                <option value="EX_ABSTRACT_ACCEPT" <?php if($status == "EX_ABSTRACT_ACCEPT"){echo "selected";}?>>EX_ABSTRACT_ACCEPT</option>
                                <option value="EX_ABSTRACT_REJECT" <?php if($status == "EX_ABSTRACT_REJECT"){echo "selected";}?>>EX_ABSTRACT_REJECT</option>
                                <option value="DISMISS" <?php if($status == "DISMISS"){echo "selected";}?>>DISMISS</option>
                                <option value="COMPLETE" <?php if($status == "COMPLETE"){echo "selected";}?>>COMPLETE</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="status">LOA Status</label>
                        <select name="loa_status" class="form-control" id="loa_status">
                            <option value="NA" <?php if($loa_status == "NA"){echo "selected";}?>>NA</option>
                            <option value="LOA" <?php if($loa_status == "LOA"){echo "selected";}?>>LOA</option>
                            <option value="LOA_ERROR" <?php if($status == "LOA_ERROR"){echo "selected";}?>>LOA_ERROR</option>                            
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label for="payment_status">Payment Status</label>
                    <select name="payment_status" class="form-control" id="payment_status">
                        <option value="UNDIFINED" <?php if($payment_status == "UNDEFINED"){echo "selected";}?>>UNDEFINED</option>
                        <option value="UNPAID" <?php if($payment_status == "UNPAID"){echo "selected";}?>>UNPAID</option>
                        <option value="REMARK" <?php if($payment_status == "REMARK"){echo "selected";}?>>REMARK</option>
                        <option value="PAID" <?php if($payment_status == "PAID"){echo "selected";}?>>PAID</option>
                        <option value="ACCEPT" <?php if($payment_status == "ACCEPT"){echo "selected";}?>>ACCEPT</option>
                        <option value="REJECT" <?php if($payment_status == "REJECT"){echo "selected";}?>>REJECT</option>
                        <option value="UPLOAD" <?php if($payment_status == "UPLOAD"){echo "selected";}?>>UPLOAD</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="presentation_status">Presentation Status</label>
                <select name="presentation_status" class="form-control" id="presentation_status">
                    <option value="NA" <?php if($presentation_status == "NA"){echo "selected";}?>>NA</option>
                    <option value="ACCEPT" <?php if($presentation_status == "ACCEPT"){echo "selected";}?>>ACCEPT</option>
                    <option value="REJECT" <?php if($presentation_status == "REJECT"){echo "selected";}?>>REJECT</option>
                    <option value="UPLOAD" <?php if($presentation_status == "UPLOAD"){echo "selected";}?>>UPLOAD</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="uitmctkt">Extended Abstract Status</label>
            <select name="abstract_status" class="form-control" id="abstract_status">
                <option  value="NA" <?php if($abstract_status == "NA"){echo "selected";}?>>NA</option>
                <option value="UPLOAD" <?php if($abstract_status == "UPLOAD"){echo "selected";}?>>UPLOAD</option>
                <option value="ACCEPT" <?php if($abstract_status == "ACCEPT"){echo "selected";}?>>ACCEPT</option>
                <option value="REJECT" <?php if($abstract_status == "REJECT"){echo "selected";}?>>REJECT</option>
                <option value="ABSTRACT_ACCEPT" <?php if($abstract_status == "ABSTRACT_ACCEPT"){echo "selected";}?>>ABSTRACT_ACCEPT</option>
                <option value="ABSTRACT_REJECT" <?php if($abstract_status == "ABSTRACT_REJECT"){echo "selected";}?>>ABSTRACT_REJECT</option>
                <option value="COMPLETE" <?php if($abstract_status == "COMPLETE"){echo "selected";}?>>COMPLETE</option>
                <option value="REVIEW" <?php if($abstract_status == "REVIEW"){echo "selected";}?>>REVIEW</option>
                <option value="CORRECTION" <?php if($abstract_status == "CORRECTION"){echo "selected";}?>>CORRECTION</option>
                <option value="CORRECTION_REMARK" <?php if($abstract_status == "CORRECTION_REMARK"){echo "selected";}?>>CORRECTION REMARK</option>
            </select>
        </div>
    </div>

</div>
<div class="row">
    <div class="col-md-4">
       <div class="form-group">
        <label for="fees">Student Card Link</label>
        <input type="text" class="form-control" id="student_card_link" name="student_card_link" value="<?php echo $student_card_link;?>">
    </div>
</div>

<div class="col-md-4">
   <div class="form-group">
    <label for="abstract_link">Abstract Link</label>
    <input type="text" class="form-control" id="abstract_link" name="abstract_link" value="<?php echo $abstract_link;?>">
</div>
</div>
<div class="col-md-4">
   <div class="form-group">
    <label for="present_link">Presentation Link</label>
    <input type="text" class="form-control" id="present_link" name="presentation_link" value="<?php echo $presentation_link;?>">
</div>
</div>
</div>
<div class="row">
    <div class="col-md-6">
       <div class="form-group">
        <label for="abstract_link">Extended Abstract File</label>
        <a href="../upload/abstract/<?php echo $ex_abstract_file?>" class="btn btn-default mr-1 mb-1 float-right">Download</a>
        <a href="./update_project.php?delete=abstract_file&id=<?php echo $project_id?>" class="btn btn-danger mr-1 mb-1 float-right">Delete</a>
        <input type="file" class="form-control" id="abstract_link" name="ex_abstract_file" >
    </div>
</div>
<div class="col-md-6">
   <div class="form-group">
    <label for="turnitin_file">Turnitin File</label>
    <a href="../upload/turnitin/<?php echo $turnitin_file?>" class="btn btn-default mr-1 mb-1 float-right">Download</a>
    <a href="./update_project.php?delete=turnitin_file&id=<?php echo $project_id?>" class="btn btn-danger mr-1 mb-1 float-right">Delete</a>
    <input type="file" class="form-control" id="turnitin_file" name="turnitin_file" >
</div>
</div>
</div>
<button type="submit" name="update" class="btn btn-default">Update</button>
</form>
</div>

<!--Add Item Modal -->
</div>
<!-- /.card -->
</div>
<div class="col-md-12">
    <div class="card card-success">
        <div class="card-header with-border">
            Member Details
            <div class="card-tools pull-right">                                            
                <button type="button" class="btn btn-card-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="./update_project.php?id=<?=$project_id;?>">
                <?php
                $sql_member = "SELECT * FROM project_member WHERE project_id='$project_id' ORDER BY position ASC";

                $result_member = $db_handle->run($sql_member);
                $last_position = 0;
                while ($row = mysqli_fetch_assoc($result_member)) {
                                                # code...
                    $id = $row["id"];
                    $number = $row["position"];
                    $name = $row['name'];
                    $email = $row['email'];
                    $organisation = $row['organisation'];
                    $role = $row['role'];
                    $last_position++;

                    ?>
                    <div class="form-row">
                        <div class="form-group col-md-1">
                            <label for="number">Position</label>
                            <input type="number" class="form-control" id="number" name="number[]" value="<?php echo $number;?>">
                            <input type="hidden" name="id[]" value="<?php echo $id; ?>" />
                        </div>
                        <div class="form-group col-md-3">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name[]" value="<?php echo $name;?>">

                        </div>
                        <div class="form-group col-md-2">
                            <label for="name">email</label>
                            <input type="text" class="form-control" id="name" name="email[]" value="<?php echo $email;?>">

                        </div>
                        <div class="form-group col-md-3">
                            <label for="name">Organisation</label>
                            <textarea name="organisation[]" class="form-control"><?php echo $organisation;?></textarea> 

                        </div>
                        <div class="form-group col-md-2">
                            <label for="name">Role</label>
                            <input type="text" class="form-control" id="name" name="role[]" value="<?php echo $role;?>">

                        </div>
                        <div class="form-group col-md-1">
                            <label for="name"></label>
                            <a href="./update_project.php?member_delete&project_id=<?=$project_id;?>&id=<?=$id;?>" class="btn btn-danger">delete</a>
                        </div>



                    </div>

                    <?php
                }
                ?>

                <input type="submit" name="update_member" class="btn btn-default" value="Update Members" / >
            </form>
        </div>

    </div>

</div>
<!-- /.row -->
<div class="col-md-12">
    <div class="card card-success">
        <div class="card-header with-border">
            Add New Members
            <div class="card-tools pull-right">                                            
                <button type="button" class="btn btn-card-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group col-md-1">
                        <label for="number">Position</label>
                        <input type="number" class="form-control" id="number" name="number" required="" value="<?php echo ++$last_position;?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required="">

                    </div>
                    <div class="form-group col-md-2">
                        <label for="email">email</label>
                        <input type="text" class="form-control" id="email" name="email" required="">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name">Organisation</label>
                        <textarea name="organisation" class="form-control"></textarea> 

                    </div>
                    <div class="form-group col-md-2">
                        <label for="role">Role</label>
                        <input type="text" class="form-control" id="role" name="role">
                    </div>
                </div>
                <input type="hidden" name="project_id" value="<?php echo $project_id;?>" / >
                <input type="submit" name="add_member" class="btn btn-default" value="Add Members" / >
            </form>
        </div>

    </div>

</div>

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
