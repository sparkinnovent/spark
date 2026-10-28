<?php
session_start();
require_once __DIR__ . '/../security.php';
csrf_auto_inject();

// Admin console: refuse to render without an authenticated administrator.
require_admin();
require_once '../Dbcontroller.php';
require_once '../Util.php';
require_once './Usersession.php';

$db_handle = new DBController();
$util = new Util();
$user = new Usersession();


$login = $user->getSession();
if (!$login) {
  $util->gotohome();
}

if (isset($_GET["tab"])) {
  $active_tab = $_GET["tab"];
}else{
  if (isset($user_scope) && $user_scope == "KHAS") {
    $active_tab  = "tab_110";
  }else{
    $active_tab  = "tab1";
  }
}

if (isset($_GET["tab_ex"])) {
  $active_tab_ex = $_GET["tab_ex"];
}else{
  $active_tab_ex  = "tab1";
}

if (isset($_GET["tab_cert"])) {
  // FIX M4 — this read $_GET["tab_ex"], so the certificate tab never activated
  // and threw an undefined-key warning when tab_ex was absent.
  $active_tab_cert = $_GET["tab_cert"];
}else{
  $active_tab_cert  = "tab1";
}


//handle msg 
if(isset($_GET["success"])){

  $success = $_GET["success"];

    //alert success send email notification and update registration's status to NOTIFY
  if($success == "notify"){
    $msg_success = "Email notification sent.";
  }

    //update payment status into accept or reject
  if($success == "verify"){
    $msg_success = "Payment verification success.";
  }

    //upload participant success
  if($success == "upload"){
    $msg_success = "upload participant record success.";
  }

     //delete problem upload participant success
  if($success == "deleteUpload"){
    $msg_success = "delete upload record success.";
  }

     //delete problem upload participant success
  if($success == "delete_project"){
    $msg_success = "delete project success.";
  }

     //upload and send email to receipent
  if($success == "receipt"){
    $msg_success = "receipt successfully uploaded and the participant were notified thru email";
  }

     //generate cert
  if($success == "cert"){
    $msg_success = "Certificate successfully generated";
  }

  if($success == "award"){
    $msg_success = "Award Cert successfully generated";
  }

    //send Cert
  if($success == "certificate"){
    $msg_success = "Certificate successfully emailed to the participant";
  }

     //delete problem upload participant success
  if($success == "delete_cert"){
    $msg_success = "delete certificate success.";
  }

  if($success == "members"){
    $msg_success = "Add new member success. New member can now sign in using his/her credential";
  }

  if($success == "deleteProject"){
    $msg_success = "Delete project success!";
  }

  if($success == "verify_presentation"){
    $msg_success = "Verify presentation was successful. Email was sent to notify the participant.";
  }

  if($success == "verify_ex_abstract"){
    $msg_success = "Verify extended abstract file(s) was successful. Email was sent to notify the participant.";
  }

  if($success == "review"){
    $msg_success = "Send review for extended abstract was successful. Email was sent to notify the participant.";
  }

  if($success == "review_complete"){
    $msg_success = "Mark review complete without review file for extended abstract was successful. Email was sent to notify the participant.";
  }

}

if(isset($_GET["fail"])){

  $fail = $_GET["fail"];

    //alert success send email notification and update registration's status to NOTIFY
  if($fail == "notify"){
    $msg_error = "Email notificaiton to the project leader failed.";
  }

  if($fail == "verify"){
    $msg_error = "Payment verification failed.";
  }

  if($fail == "upload"){
    $msg_error = "Upload participant record failed.";
  }

     //delete problem upload participant fail
  if($fail == "deleteUpload"){
    $msg_error = "delete upload record failed.";
  }

     //delete testing project
  if($fail == "delete_project"){
    $msg_error = "delete project failed.";
  }

    //delete testing project
  if($fail == "receipt"){
    $msg_error = "upload receipt failed! please ask atif";
  }

     //generate cert
  if($fail == "cert"){
    $msg_error = "Certificate failed to generate";
  }

     //generate award
  if($fail == "award"){
    $msg_error = "Award certificate failed to generate";
  }

    //send Cert
  if($fail == "certificate"){
    $msg_error = "Failed to send certificate thru email to participant";    
  }

     //delete cert
  if($fail == "delete_cert"){
    $msg_error = "delete certificate failed.";
  }

  if($fail == "members"){
    $msg_error = "Insert new member failed. please try again. or contact atif";
  }

  if($fail == "members_exist"){
    $msg_error = "Insert new member failed. Email address already used. please use different email address or reset password.";
  }
  if($fail == "loa_not_found"){
    $msg_error = "LOA was not found. Please generate LOA first";
  }
  if($fail == "verify_presentation"){
    $msg_error = "Verify presentation was failed. Please contact atif.";
  }
  if($fail == "verify_ex_abstract"){
    $msg_error = "Verify extended abstract file(s) was failed. Please contact atif.";
  }
  if($fail == "review"){
    $msg_error = "Submit review for extended abstract was failed. Please contact atif.";
  }
  if($fail == "review_file"){
    $msg_error = "Submit review for extended abstract was failed. Please upload PDF file only.";
  }
  if($fail == "review_complete"){
    $msg_error = "Mark complete for extended abstract was failed. Please contact Atif.";
  }
}
?>
<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GDIID | ADMIN</title>

  <?php include './header-style.php'; ?>

  <link href="../dist/img/uitm-icon.png" rel="icon">
</head>
<body class="hold-transition layout-top-nav">
  <div class="wrapper">

    <!-- Navbar -->
    <?php include './nav.php';?>
    <!-- /.navbar -->

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <!-- <div class="content-header"> -->
        <!-- <div class="container"> -->
          <!-- <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0"> GDIID 2021 <small>Dashboard</small></h1>
            </div> --><!-- /.col -->
            <!-- <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>

              </ol>
            </div> --><!-- /.col -->
            <!-- </div> --><!-- /.row -->
            <!-- </div> --><!-- /.container-fluid -->
            <!-- </div> -->
            <!-- /.content-header -->

            <!-- Main content -->
            <div class="content">
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
              <!-- <div class="container"> -->
                <div class="row">

                  <div class="col-lg-12 col-xl-12" id="accordion">
                    <div class="card">
                      <div class="card-header p-2">
                        <ul class="nav nav-pills">
                          <li class="nav-item"><a class="nav-link <?php if(isset($active_tab) && $active_tab=="tab1"){echo("active");}?>" href="#participant" data-toggle="tab">Participant</a></li>
                          <li class="nav-item"><a class="nav-link <?php if(isset($active_tab) && $active_tab=="tab2"){echo("active");}?>" href="#payment" data-toggle="tab">Verify Payment</a></li>
                          <li class="nav-item"><a class="nav-link <?php if(isset($active_tab) && $active_tab=="tab3"){echo("active");}?>" href="#presentation" data-toggle="tab">Presentation</a></li>
                          <li class="nav-item"><a class="nav-link <?php if(isset($active_tab) && $active_tab=="tab4"){echo("active");}?>" href="#ex_abstract" data-toggle="tab">Extended Abstract</a></li>
                          <li class="nav-item"><a class="nav-link <?php if(isset($active_tab) && $active_tab=="tab5"){echo("active");}?>" href="#certificate" data-toggle="tab">Certificate</a></li>
                        </ul>
                      </div><!-- /.card-header -->
                      <div class="card-body">
                        <div class="tab-content">
                          <div class="tab-pane <?php if ($active_tab == "tab1") {echo 'active';}?>" id="participant">

                            <table id="example3" class="table table-bordered table-striped">
                              <thead>
                               <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Fees</th>
                                <th>Upload ID</th>
                                <th>Status</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                             <?php
                             $query = "Select * from project order by id ASC";
                             $result_project_list = $db_handle->runBaseQuery($query);
                             
                             if (!empty($result_project_list)) {
                              foreach ($result_project_list as $key => $value) {
                                $country = $result_project_list[$key]["country"];
                                $international = 0;
                                if ($country != "MALAYSIA") {
                                  $international = 1;
                                }
                                ?>
                                <tr>
                                  <td><?php
                                  $id = $result_project_list[$key]["id"];
                                  echo $id;

                                ?></td>
                                <td><?php
                                echo $result_project_list[$key]["email"];
                    //echo "Title: <b>".$result_project_list[$key]["title"]."</b>";
                                ;
                              ?></td>
                              <td><?php
                    //echo "Email:<b>".$result_project_list[$key]["email"] . "</b><br>";
                              echo $result_project_list[$key]["title"];
                              ;
                            ?></td>
                            <td><?php
                            echo $result_project_list[$key]["cat_iid"];
                            ;
                          ?></td>
                          <td><?php
                          echo $result_project_list[$key]["currency"]." ".$result_project_list[$key]["fees"];
                          ;
                        ?></td>
                        <td><?php
                        echo $result_project_list[$key]["upload_id"];
                        ;
                      ?></td>
                      <td>       
                        <?php
                        echo $result_project_list[$key]["status"];
                        ?>
                      </td>
                      <td>        
                        <?php
                        echo $result_project_list[$key]["payment_status"];
                        ;
                        ?>
                      </td>
                      <td>

                        <div class="btn-group btn-group-sm">

                          <a href="../view-loa.php?mail=<?php echo $result_project_list[$key]["email"];?>&id=<?php echo $id;?>" class="btn btn-primary" title="View LOA" > <i class="far fa-eye"></i></a>

                          <?php
                          if ($result_project_list[$key]["uitmctkt"] == 1) {
                            ?>
                            <a href="../send-loa-kt.php?mail=<?php echo $result_project_list[$key]["email"];
                          ?>&id=<?php echo $id;?>" class="btn btn-success" title="send email with LOA" > <i class="fa fa-share"></i></a>
                          <?php
                        }else{

                          if ($international == 1) {
                            ?>
                            <a href="../send-loa-upload.php?mail=<?php echo $result_project_list[$key]["email"];
                          ?>&id=<?php echo $id;?>" class="btn btn-default" title="upload LOA for International Participant" > <i class="fa fa-upload"></i></a>
                          <?php
                        }
                        ?>
                        <a href="../send-loa-pub.php?mail=<?php echo $result_project_list[$key]["email"];
                      ?>&id=<?php echo $id;?>" class="btn btn-success" title="send email with LOA" > <i class="fa fa-share"></i></a>
                      <?php
                    }
                    ?>
                    <?php echo '<a class="btn btn-warning" href="update_project.php?id='.$id.'" title="Update project"><i class="fa fa-pen"></i></a>';?>
                    <?php echo '<form method="post" action="deleteProject.php" class="d-inline" onsubmit="return confirm(\"Delete this project and all its records?\")">' . csrf_field() . '<input type="hidden" name="id" value="'.$id.'"><button type="submit" class="btn btn-danger" title="Delete project"><i class="far fa-trash-alt"></i></button></form>';?>
                  </div>
                </td>

              </tr>
              <?php
            }
          }
          ?>

        </tbody>
      </table>

    </div>

    <div class="tab-pane <?php if ($active_tab == "tab2") {echo 'active';}?>" id="payment">

      <div class="row mb-3">
        <div class="col-sm-8"><h3 id="ver_payment_rate_info" class="card-title">Payment Rate Info</h3></div>
        <div class="col-sm-4">  <button type="button" class="btn btn-warning float-right" data-toggle="modal" data-target="#modal-add-payment-fees"><i class="fas fa-plus"></i> Add New Payment</button></div>
      </div>
      <table id="example4a" class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Date end</th>
            <th>Currency</th>
            <th>Rate</th>
            <th>Remark</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = "SELECT * FROM payment_rate";
          $result_fees_rate = $db_handle->runBaseQuery($query);

          if (!empty($result_fees_rate)) {
            foreach ($result_fees_rate as $key => $value) {
              ?>
              <tr>
                <td>
                  <?php 
                  $fees_id = $result_fees_rate[$key]["id"];
                  echo $result_fees_rate[$key]["name"];
                  ?>
                </td>
                <td>
                  <?php 
                  echo $result_fees_rate[$key]["category"];
                  ?>
                </td>
                <td>
                  <?php 
                  echo $result_fees_rate[$key]["date_end"];
                  ?>
                </td>
                <td>
                  <?php 
                  echo $result_fees_rate[$key]["currency"];
                  ?>
                </td>
                <td>
                  <?php 
                  echo $result_fees_rate[$key]["rate"];
                  ?>
                </td>
                <td>
                  <?php 
                  echo $result_fees_rate[$key]["remark"];
                  ?>
                </td>
                <td>
                  <a href="./update_fees.php?id=<?php echo $fees_id;?>" class="btn btn-warning btn-block">Update</a>
                  <a href="<?php echo csrf_url("./fees.process.php?delete&id=" . (int)$fees_id); ?>" class="btn btn-danger btn-block">Delete</a>
                </td>
              </tr>
              <?php
            }
          }
          ?>
        </tbody>
      </table>

      <div class="row mb-3">
        <div class="col-sm-8"><h3 id="ver_incomplete_payment" class="card-title">Incomplete Verification</h3></div>
        <div class="col-sm-4"><a href="#ver_complete_payment" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Completed</a></div>
      </div>

      <table id="example4" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Payment Info</th>
            <th>Project</th>
            <th>Proof payment</th>
            <th>Accept</th> 
            <th>Reject</th>
          </tr>
        </thead>
        <tbody>

          <?php
          $query = "Select a.title, a.currency, a.cat_iid, a.organisation, a.uitmctkt, a.fees AS p_fees, b.* from project a JOIN project_payment b ON a.id=b.project_id WHERE a.payment_status='UPLOAD' OR a.payment_status='REMARK' order by a.id desc";
          $result2 = $db_handle->runBaseQuery($query);

          if (!empty($result2)) {
            foreach ($result2 as $key => $value) {
              ?>
              <tr>
                <td>
                  <?php 

                  $payment_date =  $result2[$key]["payment_date"];
                  $uitmkt =  $result2[$key]["uitmctkt"];
                  $pay_currency = $result2[$key]["currency"];
                  $payment_method = $result2[$key]["payment_method"];

              //kalau ubah early bird, sila tukar sini
                  $early_bird =strtotime("2023-07-17");
                  $early_bird = date("Y-m-d", $early_bird);

                  if (!$uitmkt) {
                // public participant

                    if ($payment_date <= $early_bird) {
                      $payment_category = "Early Bird";

                  //kena tau dia ni malaysian ke bukan
                      if ($pay_currency !="MYR") {
                  // orang international
                        $pay_fee = "40";
                      }else{
                  //orang malaysia
                        $pay_fee = "100";
                      }

                    }else{
                      $payment_category = "Normal";

                  //kena tau dia ni malaysian ke bukan
                      if ($pay_currency !="MYR") {
                  // orang international
                        $pay_fee = "50";
                      }else{
                  //orang malaysia
                        $pay_fee = "150";
                      }
                    }

                    $payment_fee = $pay_currency." ". $pay_fee;

                  }else{
                //geng uitmctkkt
                    $payment_category = "UITMCTKT";
                    //$payment_method = "Account Transfer";
                    $pay_fee_new = "85"; //new rate for uitmctkt
                    $payment_fee = $pay_currency." ".$pay_fee_new;
                  }
                  ?>
                  Payment Date: <br><b><?php echo $payment_date;?></b><br>
                  Payment Rate: <br><b><?php //echo $payment_fee;?>Please Select</b><br>
                  Fee Category: <br><b><?php echo $payment_category;?></b><br>
                  Payment Method: <br><b><?php echo $payment_method;?></b><br>

                </td>
                <td>Project ID: <b><?php echo $result2[$key]["project_id"];?></b><br>
                  Title: <b><?php echo $result2[$key]["title"]. "<br>"; ?></b>
                  Catergory IID: <b><?php
                  $cat_iid = $result2[$key]["cat_iid"];
                  $cat_iid = $util->getCategory($cat_iid);
                  echo $cat_iid;
                ?></b>
                <br>
                Organisation: <b><?php echo $result2[$key]["organisation"]; 
              ?></b><br>

              UITMCTKKT: <b><?php $uitmkt = $result2[$key]["uitmctkt"]; 
              if ($uitmkt) {
                echo "YES<br>";
              }else{
                echo "NO<br>";
              }
            ?></b>

          </td>
          <td><?php                                      
          $source = $result2[$key]['payment_proof'];                                
          echo "<a target='_blank' href='../upload/payment/".$source."'>".$source."</a>";
          ?>
        </td>
        <td>
          <form action="verify_payment.process.php?&id=<?php echo $result2[$key]["project_id"];?>" method="post" >
            <div class="form-group">
              <label for="payment_rate" >Payment Rate</label>
              <select name="payment_rate" id="payment_rate" class="form-control" required="">
                <option>Please Select</option>
                <?php
                $sql_payment_rate2 = "SELECT id, name FROM payment_rate";
                $result_payment_rate2 = $db_handle->run($sql_payment_rate2);
                while ($row_payment_rate2 = mysqli_fetch_assoc($result_payment_rate2)) {
                  echo '<option value="'.$row_payment_rate2["id"].'">'.$row_payment_rate2["name"].'</option>';
                }
                ?>
              </select>
            </div>
            <input type="submit" class="btn btn-success btn-block" name="accept" value="Accept" />
          </form>

        </td>
        <td>
          <form class="" action="verify_payment.process.php?reject&id=<?php echo $result2[$key]["project_id"];?>&payment_id=<?php echo $result2[$key]["id"];?>" method="post">
            <div class="form-group">
              <label for="reason" >Reason</label>
              <select name="reason" id="reason" class="form-control" required="">
                <option>Please Select</option>
                <option value="Proof of payment no clear">Proof of payment no clear</option>
                <option value="Proof of payment is wrong">Proof of payment is wrong</option>
                <option value="Payment not enough">Payment not enough</option>
                <option value="Not in record">Not in record</option>
                <option value="Others">Others</option>
              </select>
            </div>
            <div class="form-group">
              <label for="comment">Comment</label>
              <textarea name="comment" id="comment" class="form-control" rows="2" >Optional</textarea>
            </div>

            <input type="submit" class="btn btn-danger btn-block" name="reject" value="Reject" />

          </form>                                      
        </td>
      </tr>
      <?php
    }
  }
  ?>

</tbody>

</table>

<div class="row mb-3 mt-3">
  <div class="col-sm-8"><h3 id="ver_complete_payment" class="card-title">Completed verification</h3></div>
  <div class="col-sm-4"><a href="#ver_incomplete_payment" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Incomplete</a></div>
</div>

<!-- payment verification record. list siapa yg verify bila-->
<table id="example5" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Project ID</th>
      <th>EMail / Title</th>
      <th>Payment Info</th>
      <th>Status</th>
      <th>Remarks (if Rejected)</th>
      <th>Verified By</th>
      <th>Verified Date Time</th> 

    </tr>
  </thead>
  <tbody>

    <?php
    $query = "SELECT p.id, p.email, p.title, p.status, p.payment_status, pp.payment_date, pp.payment_proof, pp.reject_reason, pp.reject_comment, pp.verification_timestamp, m.member_name FROM project p LEFT JOIN project_payment pp ON p.id = pp.project_id JOIN members m ON pp.verified_by = m.member_id ORDER BY p.id ASC";
    $result2 = $db_handle->runBaseQuery($query);

    if (!empty($result2)) {
      foreach ($result2 as $key => $value) {
        ?>
        <tr>
          <td><?php
          $id = $result2[$key]["id"];
          echo $id;
          ;
        ?></td>
        <td><?php                                                       
        echo "Email: ".$result2[$key]["email"];
        echo "<br>";
        echo "Title: ".$result2[$key]["title"];
      ?></td>
      <td><?php                                                       
      echo "Date: ".$result2[$key]["payment_date"];
      echo "<br>";
      $payment_proof = $result2[$key]["payment_proof"];
      echo "Proof: <a target='_blank' href='../upload/payment/".$payment_proof."' >".$payment_proof."</a>";
    ?></td>
    <td><?php
    $status = $result2[$key]["payment_status"];
    echo $status;
    ?>
  </td>
  <td>
    <p>If the status is ACCEPT, there is NO Remark</p>
    <?php
    $reject_reason = $result2[$key]["reject_reason"];
    $reject_comment = $result2[$key]["reject_comment"];
    echo "Reject Reason: ".$reject_reason;
    echo "<BR>";
    echo "Reject Comment: ".$reject_comment;
    ?>
  </td>
  <td><?php
  $verified_by = $result2[$key]["member_name"];
  echo $verified_by;
  ?>
</td>
<td><?php
$verified_datetime = $result2[$key]["verification_timestamp"];
echo $verified_datetime;
?>
</td>

</tr>
<?php
}
}
?>
</tbody>
</table>
</div> <!-- tab-pane tab3 -->


<div class="tab-pane <?php if ($active_tab == "tab3") {echo 'active';}?>" id="presentation">

  <div class="row mb-3">
    <div class="col-sm-8"><h3 id="ver_incomplete_presentation" class="card-title">Incomplete Verification</h3></div>
    <div class="col-sm-4"><a href="#ver_complete_presentation" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Completed</a></div>
  </div>

  <table id="example6" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Project</th>
        <th>Video File</th>
        <th>Accept</th> 
        <th>Reject</th>
      </tr>
    </thead>
    <tbody>

      <?php
      $query = "Select id, title, cat_iid, uitmctkt, video_link from project WHERE status='PRESENT' order by id desc";
      $result2 = $db_handle->runBaseQuery($query);

      if (!empty($result2)) {
        foreach ($result2 as $key => $value) {
          ?>
          <tr>
            <td>Project ID: <b><?php echo $result2[$key]["id"];?></b>
              Title: <b><?php echo $result2[$key]["title"]. "<br>"; ?></b>
              Catergory IID: <b><?php
              $cat_iid = $result2[$key]["cat_iid"];
              $cat_iid = $util->getCategory($cat_iid);
              echo $cat_iid;
            ?></b>

            UITMCTKKT: <b><?php $uitmkt = $result2[$key]["uitmctkt"]; 
            if ($uitmkt) {
              echo "YES<br>";
            }else{
              echo "No<br>";
            }
          ?></b>

        </td>
        <td><?php                                      
        $source = $result2[$key]['video_link'];                                
        echo "<a target='_blank' href='".$source."'>".$source."<i class='fas fa-external-link-alt fa-xs'></i></a>";?>
      </td>
      <td>
        <form action="verify_presentation.process.php" method="post">
          <input type="hidden"  class="form-control" name="project_id" value="<?php echo $result2[$key]["id"];?>">
          <input type="text" class="form-control mb-2" name="youtube_link" placeholder="YouTube Link" required="">
          <input type="submit" class="form-control btn btn-success" name="video_accept" value="Accept" />
        </form>

      </td>
      <td>
        <form class="" action="verify_presentation.process.php?reject&id=<?php echo $result2[$key]["id"];?>" method="post">
          <div class="form-group">
            <label for="reason" >Reason</label>
            <select name="reason" id="reason" class="form-control" required="">
              <option>Please Select</option>
              <option value="Link not working">Link not working</option>
              <option value="unsupported Cloud Service">Use unsupported Cloud Service</option>
              <option value="Not in record">Not in record</option>
              <option value="Others">Others</option>
            </select>
          </div>
          <div class="form-group">
            <label for="comment">Comment</label>
            <textarea name="comment" id="comment" class="form-control" rows="2" >Optional</textarea>
          </div>

          <input type="submit" class="btn btn-danger pull-right" name="reject" value="Reject" />

        </form>                                      
      </td>
    </tr>
    <?php
  }
}
?>

</tbody>

</table>

<div class="row mb-3 mt-3">
  <div class="col-sm-8"><h3 id="ver_complete_presentation" class="card-title">Completed verification</h3></div>
  <div class="col-sm-4"><a href="#ver_incomplete_presentation" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Incomplete</a></div>
</div>

<!-- presentation verification record. list siapa yg verify bila-->
<table id="example7" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Project ID</th>
      <th>Email / Title</th>
      <th>Presentation</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>

    <?php
    $query = "SELECT p.id, p.email, p.title, p.vid_yt_link, p.status FROM project p WHERE p.status='PRESENT_ACCEPT' OR p.status='PRESENT_ACCEPT' ORDER BY p.id ASC";
    $result2 = $db_handle->runBaseQuery($query);

    if (!empty($result2)) {
      foreach ($result2 as $key => $value) {
        ?>
        <tr>
          <td><?php
          $id = $result2[$key]["id"];
          echo $id;
          ;
        ?></td>
        <td><?php                                                       
        echo "Email: ".$result2[$key]["email"];
        echo "<br>";
        echo "Title: ".$result2[$key]["title"];
      ?></td>
      <td><?php                                                       
      $yt_link = $result2[$key]["vid_yt_link"];
      echo "<a target='_blank' href='".$yt_link."' >".$yt_link."</a>";
    ?></td>
    <td><?php
    $status = $result2[$key]["status"];
    echo $status;
    ?>
  </td>
</tr>
<?php
}
}
?>

</tbody>

</table>

</div>

<!-- tab5 -->

<div class="tab-pane <?php if ($active_tab == "tab4") {echo 'active';}?>" id="ex_abstract">

  <div class="card card-primary card-outline card-outline-tabs">
    <div class="card-header p-0 border-bottom-0">
      <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
        <li class="nav-item">
          <a class="nav-link <?php if ($active_tab_ex == "tab1") {echo 'active';}?>" id="custom-tabs-four-home-tab" data-toggle="pill" href="#custom-tabs-four-home" role="tab" aria-controls="custom-tabs-four-home" aria-selected="true">1. Verify File/Turnitin</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if ($active_tab_ex == "tab2") {echo 'active';}?>" id="custom-tabs-four-profile-tab" data-toggle="pill" href="#custom-tabs-four-profile" role="tab" aria-controls="custom-tabs-four-profile" aria-selected="false">2. Reviewer Comment</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if ($active_tab_ex == "tab3") {echo 'active';}?>" id="custom-tabs-four-messages-tab" data-toggle="pill" href="#custom-tabs-four-messages" role="tab" aria-controls="custom-tabs-four-messages" aria-selected="false">3. Verify Correction</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if ($active_tab_ex == "tab4") {echo 'active';}?>" id="custom-tabs-four-settings-tab" data-toggle="pill" href="#custom-tabs-four-settings" role="tab" aria-controls="custom-tabs-four-settings" aria-selected="false">4. Complete</a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="custom-tabs-four-tabContent">
        <div class="tab-pane fade <?php if ($active_tab_ex == "tab1") {echo 'active show';}?>" id="custom-tabs-four-home" role="tabpanel" aria-labelledby="custom-tabs-four-home-tab">

         <div class="row mb-3">
          <div class="col-sm-8"><h3 id="ver_incomplete_abstract_tab1" class="card-title">Incomplete Verification</h3></div>
          <div class="col-sm-4"><a href="#ver_complete_abstract_tab1" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Completed</a></div>
        </div>

        <table id="example8" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Project</th>
              <th>Extended Abstract</th>
              <th>Turnitin Report</th>
              <th>Accept</th> 
              <th>Reject</th>
            </tr>
          </thead>
          <tbody>

            <?php
            $query = "Select id, title, cat_iid, uitmctkt, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='UPLOAD' OR abstract_status='REMARK' order by id desc";
            $result2 = $db_handle->runBaseQuery($query);

            if (!empty($result2)) {
              foreach ($result2 as $key => $value) {
                ?>
                <tr>

                  <td>Project ID: <b><?php echo $result2[$key]["id"];?></b>
                    Title: <b><?php echo $result2[$key]["title"]. "<br>"; ?></b>
                    Catergory IID: <b><?php
                    $cat_iid = $result2[$key]["cat_iid"];
                    $cat_iid = $util->getCategory($cat_iid);
                    echo $cat_iid;
                  ?></b>

                  UITMCTKKT: <b><?php $uitmkt = $result2[$key]["uitmctkt"]; 
                  if ($uitmkt) {
                    echo "YES<br>";
                  }else{
                    echo "No<br>";
                  }
                ?></b>

              </td>
              <td><?php                                      
              $ex_abstract_file = $result2[$key]['ex_abstract_file'];
              echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

              echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

              ?>
            </td>
            <td><?php                                      
            $turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
            echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
            ?>
          </td>
          <td>
            <a href="../verify_ex_abstract.process.php?accept&id=<?php echo $result2[$key]["id"];?>" class="btn btn-success" data-toggle="tooltip" title="accept" > <i class="fa fa-check"></i></a>

          </td>
          <td>
            <form class="" action="../verify_ex_abstract.process.php?reject&id=<?php echo $result2[$key]["id"];?>" method="post">
              <div class="form-group">
                <label for="reason" >Reason</label>
                <select name="reason" id="reason" class="form-control" required="">
                  <option >Please Select</option>
                  <option value="Turnitin similarity index exceed 30%">Turnitin similarity index exceed 30%</option>
                  <option value="Not well-formatted">Not well-formatted</option>
                  <option value="Wrong document(s) uploaded">Wrong document(s) uploaded</option>
                  <option value="Others">Others</option>
                </select>
              </div>
              <div class="form-group">
                <label for="comment">Comment</label>
                <textarea name="comment" id="comment" class="form-control" rows="2" >Optional</textarea>
              </div>

              <input type="submit" class="btn btn-danger pull-right" name="reject" value="Reject" />

            </form>                                      
          </td>
        </tr>
        <?php
      }
    }
    ?>

  </tbody>

</table>


<div class="row mb-3 mt-3">
  <div class="col-sm-8"><h3 id="ver_complete_abstract_tab1" class="card-title">Completed Verification</h3></div>
  <div class="col-sm-4"><a href="#ver_incomplete_abstract_tab1" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Incomplete</a></div>
</div>

<table id="example8a" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Project ID</th>
      <th>Title</th>
      <th>Category</th>
      <th>Email</th>
      <th>Author(s)</th>
      <th>Extended Abstract</th>
      <th>Turnitin Report</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>

    <?php
    $query = "Select id, title, cat_iid, uitmctkt, abstract_status, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='ACCEPT' order by id desc";
    $result2 = $db_handle->runBaseQuery($query);

    if (!empty($result2)) {
      foreach ($result2 as $key => $value) {
        ?>
        <tr>
          <td><?php
          $project_id_tab1_kakya = $result2[$key]["id"];
          echo $project_id_tab1_kakya;?></td>
          <td><?php echo $result2[$key]["title"];?></td>
          <td><?php  
          $cat_iid = $result2[$key]["cat_iid"];
          $cat_iid = $util->getCategory($cat_iid);
          echo $cat_iid;?></td>
          <td><?php echo $result2[$key]["email"];?></td>

          <td>
            <?php 

            $sql_authors = "SELECT name FROM project_member WHERE project_id = '$project_id_tab1_kakya' ORDER BY position ASC";
            $result_authors_tab1 = $db_handle->run($sql_authors);
            $author_tab1_kakya = NULL;
            while ($row_tab1_kakya = mysqli_fetch_assoc($result_authors_tab1)) {
              if ($author_tab1_kakya == NULL) {
                $author_tab1_kakya = $row_tab1_kakya["name"];
              }else{
                $author_tab1_kakya = $author_tab1_kakya. ",<br>". $row_tab1_kakya["name"];
              }
            }

            echo $author_tab1_kakya;
            ?>
          </td>

          <td><?php                                      
          $ex_abstract_file = $result2[$key]['ex_abstract_file'];
          echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

          echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

          ?>
        </td>
        <td><?php                                      
        $turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
        echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
        ?>
      </td>
      <td><?php echo $result2[$key]["abstract_status"];?></td>

    </tr>
    <?php
  }
}
?>

</tbody>

<!-- tab2 - ajk upload review comment. -->
</table>
</div>
<div class="tab-pane <?php if ($active_tab_ex == "tab2") {echo 'active show';}?> fade" id="custom-tabs-four-profile" role="tabpanel" aria-labelledby="custom-tabs-four-profile-tab">

  <div class="row mb-3">
    <div class="col-sm-8"><h3 id="ver_incomplete_abstract_tab2" class="card-title">Incomplete Verification</h3></div>
    <div class="col-sm-4"><a href="#ver_complete_abstract_tab2" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Completed</a></div>
  </div>

  <table id="example8b" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Project</th>
        <th>Extended Abstract</th>
        <th>Turnitin Report</th>
        <th>Action</th> 
      </tr>
    </thead>
    <tbody>

      <?php
      $query = "Select id, title, email, cat_iid, uitmctkt, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='ACCEPT' order by id desc";
      $result2 = $db_handle->runBaseQuery($query);

      if (!empty($result2)) {
        foreach ($result2 as $key => $value) {

          $project_id_tab2 = $result2[$key]["id"];
          $title_tab2 = $result2[$key]["title"];
          $email_tab2 = $result2[$key]["email"];
          $cat_iid_tab2 = $result2[$key]["cat_iid"];
          $cat_iid_tab2 = $util->getCategory($cat_iid_tab2);
          $cat_iid_tab2 = strtoupper($cat_iid_tab2);
          $uitmctkt_tab2 = $result2[$key]["uitmctkt"];

          if ($uitmctkt_tab2) {
            $uitmctkt_tab2 = "YES";
          }else{
            $uitmctkt_tab2 = "NO";
          }

          ?>
          <tr>

            <td>

              <table class="table table-sm table-borderless">
                <tr>
                  <td>Project ID</td>
                  <td><?php echo $project_id_tab2;?></td>
                </tr>
                <tr>
                  <td>Title</td>
                  <td><?php echo $title_tab2;?></td>
                </tr>
                <tr>
                  <td>Email</td>
                  <td><?php echo $email_tab2;?></td>
                </tr>
                <tr>
                  <td>Category</td>
                  <td><?php echo $cat_iid_tab2;?></td>
                </tr>
                <tr>
                  <td>UITMCTKKT</td>
                  <td><?php echo $uitmctkt_tab2;?></td>
                </tr>
              </table>
            </td>
            <td><?php                                      
            $ex_abstract_file = $result2[$key]['ex_abstract_file'];
            echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

            echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

            ?>
          </td>
          <td><?php                                      
          $turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
          echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
          ?>
        </td>
        <td>

          <form action="../verify_ex_abstract.process.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
              <label for="status">Status</label>
              <small class="text-muted">please select extended abstract status</small>
              <select id="status" class="form-control" required="" name="review_status">
                <option>Please Select</option>
                <option value="complete">Accept without correction</option>
                <option value="review">Accept with correction</option>
                <option value="reject">Reject</option>
              </select>
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Review File</label>
              <small class="text-muted">only accept PDF file</small>
              <input type="file" class="form-control" id="exampleInputEmail1" name="review_file">
            </div>
            <input type="hidden" name="id" value="<?php echo $project_id_tab2;?>">
            <input type="submit" class="btn btn-primary btn-block" name="submit_review" value="Send Review">
          </form>

          <hr class="mt-1 mb-1"/>

          <a href="../verify_ex_abstract.process.php?complete_no_review&id=<?php echo $project_id_tab2;?>" class="btn btn-success btn-block">Accept without correction without reviewer comment (file)</a>
        </td>

      </tr>
      <?php
    }
  }
  ?>

</tbody>

</table>


<div class="row mb-3 mt-3">
  <div class="col-sm-8"><h3 id="ver_complete_abstract_tab2" class="card-title">Completed Verification</h3></div>
  <div class="col-sm-4"><a href="#ver_incomplete_abstract_tab2" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Incomplete</a></div>
</div>

<table id="example8c" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Project ID</th>
      <th>Title</th>
      <th>Category</th>
      <th>Email</th>
      <th>Ex. Abstract/Turitin</th>
      <th>Review File</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>

    <?php
    $query = "SELECT p.id, p.title, p.cat_iid, p.uitmctkt, p.abstract_status, p.email, p.ex_abstract_file, p.turnitin_file, pr.review_file from project p JOIN project_ex_abstract_review pr ON p.id=pr.project_id WHERE p.abstract_status='REVIEW' order by p.id desc";
    $result2 = $db_handle->runBaseQuery($query);

    if (!empty($result2)) {
      foreach ($result2 as $key => $value) {
        ?>
        <tr>
          <td><?php echo $result2[$key]["id"];?></td>
          <td><?php echo $result2[$key]["title"];?></td>
          <td><?php  
          $cat_iid = $result2[$key]["cat_iid"];
          $cat_iid = $util->getCategory($cat_iid);
          echo $cat_iid;?></td>
          <td><?php echo $result2[$key]["email"];?></td>

          <td><?php                                      
          $ex_abstract_file = $result2[$key]['ex_abstract_file'];
          echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

          echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';

          $turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
          echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> Turnitin </a>';
          ?>
        </td>
        <td><?php                                      
        $review_file = $result2[$key]['review_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
        echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/review/'.$review_file.'"><i class="fas fa-eye"></i> View</a>';
        ?>
      </td>
      <td><?php echo $result2[$key]["abstract_status"];?></td>

    </tr>
    <?php
  }
}
?>

</tbody>

</table>

</div>
<!-- tab review correction -->
<div class="tab-pane <?php if ($active_tab_ex == "tab3") {echo 'active show';}?> fade" id="custom-tabs-four-messages" role="tabpanel" aria-labelledby="custom-tabs-four-messages-tab">

  <div class="row mb-3">
    <div class="col-sm-8"><h3 id="ver_incomplete_abstract_tab3" class="card-title">Incomplete Verification</h3></div>
    <div class="col-sm-4"><a href="#ver_complete_abstract_tab3" class="btn btn-primary float-right" ><i class="far fa-eye"></i> Rejected Correction</a></div>
  </div>

  <table id="example8d" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Project</th>
        <th>Extended Abstract</th>
        <th>Review Report</th>
        <th>Accept</th>
        <th>Reject</th> 
      </tr>
    </thead>
    <tbody>

      <?php
      $query = "SELECT p.id, p.title, p.cat_iid, p.uitmctkt, p.abstract_status, p.email, p.ex_abstract_file, p.turnitin_file, pr.review_file from project p JOIN project_ex_abstract_review pr ON p.id=pr.project_id WHERE p.abstract_status='CORRECTION' order by p.id desc";
      $result_ex_abstract_tab3 = $db_handle->runBaseQuery($query);

      if (!empty($result_ex_abstract_tab3)) {
        foreach ($result_ex_abstract_tab3 as $key => $value) {
          $project_id_tab3 = $result_ex_abstract_tab3[$key]["id"];
          ?>
          <tr>

            <td>Project ID: <b><?php echo $project_id_tab3;?></b>
              Title: <b><?php echo $result_ex_abstract_tab3[$key]["title"]. "<br>"; ?></b>
              Catergory IID: <b><?php
              $cat_iid = $result_ex_abstract_tab3[$key]["cat_iid"];
              $cat_iid = $util->getCategory($cat_iid);
              echo $cat_iid. "<br>";
            ?></b>

            UITMCTKKT: <b><?php $uitmkt = $result_ex_abstract_tab3[$key]["uitmctkt"]; 
            if ($uitmkt) {
              echo "YES<br>";
            }else{
              echo "No<br>";
            }
          ?></b>

        </td>
        <td><?php                                      
        $ex_abstract_file = $result_ex_abstract_tab3[$key]['ex_abstract_file'];
        echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

        echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

        ?>
      </td>
      <td><?php                                      
      $review_file = $result_ex_abstract_tab3[$key]['review_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
      echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/review/'.$review_file.'"><i class="fas fa-eye"></i> View</a>';
      ?>
    </td>
    <td>
      <a href="../verify_ex_abstract.process.php?accept_correction&id=<?php echo $project_id_tab3;?>" class="btn btn-success btn-block">ACCEPT</a>
    </td>
    <td>
      <form action="../verify_ex_abstract.process.php" method="post">
        <div class="form-group">
          <label for="inputtextcomment">Comment</label>
          <textarea class="form-control" name="reject_comment" id="inputtextcomment" required="" rows="3"></textarea>
        </div>
        <input type="hidden" name="id" value="<?php echo $project_id_tab3;?>">
        <input type="submit" class="btn btn-primary btn-block" name="submit_reject_correction" value="Submit Reject">
      </form>
    </td>

  </tr>
  <?php
}
}
?>

</tbody>

</table>


<div class="row mb-3 mt-3">
  <div class="col-sm-8"><h3 id="ver_complete_abstract_tab3" class="card-title">Rejected Correction</h3></div>
  <div class="col-sm-4"><a href="#ver_incomplete_abstract_tab3" class="btn btn-primary float-right" ><i class="far fa-eye"></i>Incomplete Correction Review</a></div>
</div>

<table id="example8e" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Project ID</th>
      <th>Title</th>
      <th>Category</th>
      <th>Email</th>
      <th>Extended Abstract</th>
      <th>Turnitin Report</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>

    <?php
    $query = "Select id, title, cat_iid, uitmctkt, abstract_status, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='CORRECTION_REMARK' order by id desc";
    $result2 = $db_handle->runBaseQuery($query);

    if (!empty($result2)) {
      foreach ($result2 as $key => $value) {
        ?>
        <tr>
          <td><?php echo $result2[$key]["id"];?></td>
          <td><?php echo $result2[$key]["title"];?></td>
          <td><?php  
          $cat_iid = $result2[$key]["cat_iid"];
          $cat_iid = $util->getCategory($cat_iid);
          echo $cat_iid;?></td>
          <td><?php echo $result2[$key]["email"];?></td>

          <td><?php                                      
          $ex_abstract_file = $result2[$key]['ex_abstract_file'];
          echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

          echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

          ?>
        </td>
        <td><?php                                      
        $turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
        echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
        ?>
      </td>
      <td><?php echo $result2[$key]["abstract_status"];?></td>

    </tr>
    <?php
  }
}
?>

</tbody>

</table>

</div>
<div class="tab-pane <?php if ($active_tab_ex == "tab4") {echo 'active show';}?> fade" id="custom-tabs-four-settings" role="tabpanel" aria-labelledby="custom-tabs-four-settings-tab">

  <table id="example8g" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Project ID</th>
        <th>Title</th>
        <th>Category</th>
        <th>Email</th>
        <th>Extended Abstract</th>
        <th>Turnitin Report</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>

      <?php
      $query = "Select id, title, cat_iid, uitmctkt, abstract_status, email, ex_abstract_file, turnitin_file from project WHERE abstract_status='COMPLETE' order by id desc";
      $result2 = $db_handle->runBaseQuery($query);

      if (!empty($result2)) {
        foreach ($result2 as $key => $value) {
          ?>
          <tr>
            <td><?php echo $result2[$key]["id"];?></td>
            <td><?php echo $result2[$key]["title"];?></td>
            <td><?php  
            $cat_iid = $result2[$key]["cat_iid"];
            $cat_iid = $util->getCategory($cat_iid);
            echo $cat_iid;?></td>
            <td><?php echo $result2[$key]["email"];?></td>

            <td><?php                                      
            $ex_abstract_file = $result2[$key]['ex_abstract_file'];
            echo '<a class="btn btn-warning btn-block mb-1" target="_blank" href="https://docs.google.com/viewerng/viewer?url=https://gdiid.uitmkt.com/upload/abstract/'.$ex_abstract_file.'""><i class="fab fa-google-drive"></i> Google Docs</a></p>';

            echo '<a class="btn btn-success btn-block" target="_blank" href="../upload/abstract/'.$ex_abstract_file.'"><i class="fas fa-download"></i> Download</a>';      

            ?>
          </td>
          <td><?php                                      
          $turnitin_file = $result2[$key]['turnitin_file'];

        //echo "<a target='_blank' href='../upload/turnitin/".$turnitin_file."'>".$turnitin_file."</a>";
          echo '<a class="btn btn-info btn-block" target="_blank" href="../upload/turnitin/'.$turnitin_file.'"><i class="fas fa-eye"></i> View</a>';
          ?>
        </td>
        <td><?php echo $result2[$key]["abstract_status"];?></td>

      </tr>
      <?php
    }
  }
  ?>

</tbody>

</table>



</div>
</div>
</div>
<!-- /.card -->
</div>

</div> <!-- ./end tab pane -->

<div class="tab-pane <?php if ($active_tab == "tab5") {echo 'active';}?>" id="certificate">

  <div class="card card-primary card-outline card-outline-tabs">
    <div class="card-header p-0 border-bottom-0">
      <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
        <li class="nav-item">
          <a class="nav-link <?php if ($active_tab_cert == "tab1") {echo 'active';}?>" id="custom-tabs-cert-participant-tab" data-toggle="pill" href="#custom-tabs-cert-participant" role="tab" aria-controls="custom-tabs-cert-participant" aria-selected="true">1. Participation</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if ($active_tab_cert == "tab2") {echo 'active';}?>" id="custom-tabs-cert-award-tab" data-toggle="pill" href="#custom-tabs-cert-award" role="tab" aria-controls="custom-tabs-cert-award" aria-selected="false">2. Award(s)</a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="custom-tabs-four-tabContent">
        <div class="tab-pane fade <?php if ($active_tab_cert == "tab1") {echo 'active show';}?>" id="custom-tabs-cert-participant" role="tabpanel" aria-labelledby="custom-tabs-cert-participant">

          <table id="example9" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Project ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Email</th>
                <th>Certificate</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>

              <?php
              $query = "Select id, title, cat_iid, uitmctkt, payment_status, email from project WHERE payment_status='ACCEPT' order by id desc";
              $result2 = $db_handle->runBaseQuery($query);

              if (!empty($result2)) {
                foreach ($result2 as $key => $value) {
                  ?>
                  <tr>
                    <td><?php 
                    $project_id = $result2[$key]["id"];
                    echo $project_id;
                  ?></td>
                  <td><?php echo $result2[$key]["title"];?></td>
                  <td><?php  
                  $cat_iid = $result2[$key]["cat_iid"];
                  $cat_iid = $util->getCategory($cat_iid);
                  echo $cat_iid;?></td>
                  <td><?php echo $result2[$key]["email"];?></td>

                  <td><?php 
                  $sql_cert = "SELECT * FROM project_cert WHERE project_id='$project_id' ORDER BY id DESC LIMIT 1";
                  $result_cert = $db_handle->run($sql_cert);
                  $result_cert_num_rows = mysqli_num_rows($result_cert);
                  $cert_status = 1;
                  if ($result_cert_num_rows == 0) {
                    echo "N/A";
                    $cert_status = 0;
                  }else{
                    while ($row_cert_found = mysqli_fetch_assoc($result_cert)) {
                      $file = $row_cert_found["files"];
                    }
                    echo '<a target="_blank" class="btn btn-info" href="../download/cert/'.$file.'" >View</a>';
                  }
                ?></td>
                <td><?php
                if ($cert_status == 1) {
                  echo '<a class="btn btn-danger mb-1 " href="../manage-cert.php?id='.$project_id.'">Manage</a>';
                }
                echo '<a class="btn btn-success" href="../generate-cert.php?id='.$project_id.'">Generate</a>';

              ?></td>
            </tr>
            <?php
          }
        }
        ?>

      </tbody>
    </table>


  </div><!-- end of tab custom-tabs-cert-participant -->

  <div class="tab-pane fade <?php if ($active_tab_cert == "tab2") {echo 'active show';}?>" id="custom-tabs-cert-award" role="tabpanel" aria-labelledby="custom-tabs-cert-award">

    <table id="example11" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Project ID</th>
          <th>Title</th>
          <th>Category</th>
          <th>Email</th>
          <th>Certificate</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>

        <?php
        $query = "Select id, title, cat_iid, uitmctkt, payment_status, email from project WHERE payment_status='ACCEPT' order by id desc";
        $result2 = $db_handle->runBaseQuery($query);

        if (!empty($result2)) {
          foreach ($result2 as $key => $value) {
            ?>
            <tr>
              <td><?php 
              $project_id = $result2[$key]["id"];
              echo $project_id;
            ?></td>
            <td><?php echo $result2[$key]["title"];?></td>
            <td><?php  
            $cat_iid = $result2[$key]["cat_iid"];
            $cat_iid = $util->getCategory($cat_iid);
            echo $cat_iid;?></td>
            <td><?php echo $result2[$key]["email"];?></td>

            <td><?php 
            $sql_award = "SELECT * FROM project_award WHERE project_id='$project_id' ORDER BY id DESC LIMIT 1";
            $result_award = $db_handle->run($sql_award);
            $result_award_num_rows = mysqli_num_rows($result_award);
            $award_status = 1;
            if ($result_cert_num_rows == 0) {
              echo "N/A";
              $cert_status = 0;
            }else{
              while ($row_award_found = mysqli_fetch_assoc($result_award)) {
                $award_file = $row_award_found["files"];
                $award_code = $row_award_found["code"];
                $award_cert_no = $row_award_found["cert_no"];
              }
              echo '<a target="_blank" class="btn btn-info btn-block" href="../download/award/'.$award_file.'" >'.$award_code.'</a>';
            }
          ?></td>
          <td><?php
          if ($award_status == 1) {
            echo '<a class="btn btn-danger mb-1 " href="../manage-award.php?id='.$project_id.'">Manage</a>';
          }
          echo '<a class="btn btn-success" href="../generate-award.php?id='.$project_id.'">Generate</a>';

        ?></td>
      </tr>
      <?php
    }
  }
  ?>

</tbody>
</table>


</div><!-- end of tab custom-tabs-cert-participant -->



</div><!-- end of tab-content -->
</div><!-- end of card-body -->
</div><!-- end of card card-primary card-outline card-outline-tabs -->
</div><!-- end of tab_5 -->

</div>
<!-- /.tab-content -->
</div><!-- /.card-body -->
</div>
</div>
<!-- /.col-md-6 -->

<div class="col-lg-6 col-xl-6">
  <div class="card card-primary card-outline">
   <div class="card-header">
    <h5 class="card-title">Upload Participant</h5>
    <a class="btn btn-info float-right" target="_blank" href="download/GDiiD2023 Registration Form  (Responses).xlsx"><i class="fa fa-download"></i> Example</a>
  </div>
  <div class="card-body">

    <p class="card-text">
      Upload new participant. please use XSLS or XSL file only. make sure only new project<br>Please remove all links (style) before upload.
    </p>

    <form method="post" enctype="multipart/form-data" action="./upload_project.process.php?p=<?php echo basename($_SERVER['PHP_SELF']); ?>">

      <div class="form-group">
        <label>New Registration (public)</label>
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-upload"></i></span>
          </div>
          <input type="file" class="form-control" name="file" />
          <span class="input-group-append">
            <input type="submit" class="form-control btn btn-primary" name="import" value="upload" />
          </span>                     
        </div>
      </div>
    </form>

    <!--
      FIX — this form posted to upload_project_kt.process.php, a file that does
      not exist in the codebase, so the "UiTMCT only" import button 404'd. The
      live handler is the same file as above; the UiTM-only behaviour is selected
      with ?cat=uitmkt, which sets $uitmkt and writes the uitmctkt flag.
    -->
    <form method="post" enctype="multipart/form-data" action="./upload_project.process.php?cat=uitmkt&p=<?php echo basename($_SERVER['PHP_SELF']); ?>">

      <div class="form-group">
        <label>New Registration (uitmctkt only)</label>
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-upload"></i></span>
          </div>
          <input type="file" class="form-control" name="file" />
          <span class="input-group-append">
            <input type="submit" class="form-control btn btn-primary" name="import" value="upload" />
          </span>                     
        </div>
      </div>
    </form>

                   <!-- <a href="#" class="card-link">Card link</a>
                     <a href="#" class="card-link">Another link</a> -->
                   </div>
                   <!-- card body -->
                 </div>
                 <!-- card -->
               </div>
               <!-- col -->
               <div class="col-xl-6 col-lg-6">
                 <div class="card card-primary card-outline">
                  <div class="card-header">
                    <h5 class="card-title m-0">Admins</h5>
                  </div>
                  <div class="card-body">

                    <button type="button" class="btn btn-warning float-right" data-toggle="modal" data-target="#modal-add-admin">
                      Add New Member
                    </button>
                    <div id="contact_form"></div>
                    <table id="" class="table">
                     <thead>
                       <tr>
                         <th>Email</th>
                         <th>Name</th>
                         <th>Role</th>
                       </tr>
                     </thead>
                     <tbody>
                       <?php
                       $sql_member = "SELECT m.*, mr.* FROM members m JOIN members_role mr ON m.member_level=mr.code";
                       $result_member = $db_handle->run($sql_member);

                       while ($row_member = mysqli_fetch_assoc($result_member)) {
                         ?>
                         <tr>
                           <td><?php echo $row_member["member_email"];?></td>
                           <td><?php echo $row_member["member_name"];?></td>
                           <td><?php echo $row_member["jawatan"];?></td>
                         </tr>
                         <?php
                       }
                       ?>
                     </tbody>
                   </table>
                 </div>
               </div><!-- /.card -->

               <div class="card card-primary card-outline">
                <div class="card-header">
                  <h5 class="card-title m-0">Manage upload</h5>
                </div>
                <div class="card-body">
                  <table id="example2" class="table">
                   <thead>
                     <tr>
                      <th>upload ID</th>
                      <th>Upload time stamp</th>
                      <th>uploader</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                   <?php
                   $query_uploader = "SELECT DISTINCT upload_id, upload__timestamp, uploader_id FROM project";
                   $result_upload = $db_handle->runBaseQuery($query_uploader);

                   if (!empty($result_upload)) {
                    foreach ($result_upload as $key => $value) {
                      ?>
                      <tr>
                        <td><?php
                        $upload_id = $result_upload[$key]["upload_id"];
                        echo $upload_id;
                        ;
                      ?></td>
                      <td><?php                                                       
                      echo $result_upload[$key]["upload__timestamp"];
                      ;
                    ?></td>
                    <td><?php
                    $uploader_id = $result_upload[$key]["uploader_id"];
                    $sql_uploader = "SELECT member_name, member_email FROM members WHERE member_id='$uploader_id'";
                    $result_uploader = $db_handle->run($sql_uploader);
                    while($row = mysqli_fetch_assoc($result_uploader)){
                      $member_name = $row["member_name"];
                      $member_email = $row["member_email"];
                      echo $member_name." - ".$member_email;
                    }

                    ;
                  ?></td>
                  <td><?php
                                                            //add delete button                                                       
                  echo "<form method='post' action='./deleteUpload.php' class='d-inline' onsubmit='return confirm(\"Delete this upload batch and all its projects?\")'>".csrf_field()."<input type='hidden' name='id' value='".$upload_id."'><button type='submit' class='btn btn-danger'>delete</button></form>";
                  ;
                ?></td>


              </tr>
              <?php
            }
          }
          ?>
        </tbody>
      </table>
    </div>
  </div><!-- /.card -->
</div>
<!-- /.col-md-6 -->

</div>
<!-- /.row -->
</div><!-- /.container-fluid -->

<!-- modal section.... -->



<!-- verify student modal -->
<?php
if (!empty($result_project_list)) {
  foreach ($result_project_list as $key => $value) {
   ?>
   <div class="modal fade" id="modal-verify-student-<?php echo $result_project_list[$key]["id"];?>">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Verify Student Status</h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="post" action="./verify_student.process.php">
          <div class="modal-body">
            <small>make sure the link is accessible and the student ID is clear</small>

            <div class="form-group">
              <label for="title"> Title</label>           
              <textarea class="form-control" rows="3" placeholder="" disabled><?php echo $result_project_list[$key]["title"];?></textarea>
            </div>

            <div class="form-group">
              <label> Email</label>
              <input type="text" name="email_disabled" value="<?php echo $result_project_list[$key]["email"];?>" class="form-control" disabled />
            </div>

            <a href="<?php echo $result_project_list[$key]["student_card_link"];?>" class="btn btn-block btn-info" target="_blank">view student document</a>

            <div class="form-group">
              <label>Reason (if reject)</label>
              <select name="reject_reason" class="form-control">
                <option>Please Select</option>
                <option value="Link inaccessible">Link Not accessible</option>
                <option value="Image not clear">Image not clear</option>
                <option value="Not strong evidence">Not a evidence document</option>
                <option value="Others">Others</option>
              </select>
            </div>

            <div class="form-group">
              <label>comment (if reject)</label>
              <input type="text" name="reject_comment" class="form-control" />
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger btn-block" data-dismiss="modal">CLOSE</button>
            <a href="./verify_student.process.php?accept&id=<?php echo $result_project_list[$key]["id"];?>" class="btn btn-block btn-success" >APPROVE
            </a>
            <input type="hidden" name="project_id" value="<?php echo $result_project_list[$key]["id"];?>" />
            <input type="hidden" name="email" value="<?php echo $result_project_list[$key]["email"];?>" />
            <input type="submit" class="btn btn-warning btn-block" name="reject" value="REJECT" />
          </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.modal -->
  <?php
}
}

?>


<!-- add admin modal -->
<div class="modal fade" id="modal-add-admin">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add New Admin</h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="form-add-member" method="post" action="./member.process.php">
        <div class="modal-body">
          <small>make sure the email is unique</small>

          <div class="form-group">
            <label for="member_name"> Username</label>
            <input type="text" class="form-control" id="member_name" name="member_name" required />
          </div>

          <div class="form-group">
            <label for="member_password">Password</label>                    
            <input type="text" class="form-control" id="member_password" name="member_password" required />                    
          </div>
          <div class="form-group">
            <label for="member_role">Member Role</label>                    
            <select name="member_role" class="form-control" id="member_role" >
              <option>Please Select</option>
              <option value="1">Super Admin</option>
              <option value="2">Admin</option>
              <option value="3">Bendahari</option>
              <option value="4">Sijil</option>
              <option value="5">AJK</option>
            </select>                    
          </div>
          <div class="form-group">
            <label for="member_email">Email Address</label>                    
            <input type="email" class="form-control" minlength="4" id="member_email" name="member_email" required />                    
          </div>
          <div class="box-footer">

          </div>

        </div>
        <div class="modal-footer justify-content-end">
          <button type="button" class="btn btn-default" data-dismiss="modal">CLOSE</button>
          <button type="reset" class="btn btn-danger" name="reset" value="reset">
            <i class="fa fa-refresh"></i> RESET
          </button>
          <input type="submit" class="btn btn-primary" name="add" value="ADD" />
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->


<!-- add payment fees rate modal -->
<div class="modal fade" id="modal-add-payment-fees">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add New Fees</h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="form-add-member" method="post" action="./fees.process.php">
        <div class="modal-body">
          <small>make sure the fees name is unique</small>

          <div class="form-group">
            <label for="fees_name">Name</label>
            <input type="text" class="form-control" id="fees_name" name="fees_name" required />
          </div>

          <div class="form-group">
            <label for="fees_category">Category</label>                    
            <select id="fees_category" class="form-control" name="fees_category" required> 
              <option>Please Select</option>
              <option value="PUBLIC" >Public</option>
              <option value="UITMCTKT" >Uitmctkt</option>
            </select>                    
          </div>

          <div class="form-group">
            <label for="fees_date_end">Date End (optional)</label>                    
            <input type="date" class="form-control" id="fees_date_end" name="fees_date_end" />                    
          </div>

          <div class="form-group">
            <label for="fees_currency">Currency</label>                    
            <select name="fees_currency" class="form-control" id="fees_currency" >
              <option>Please Select</option>
              <option value="MYR">Malaysian Ringgit (MYR)</option>
              <option value="SGD">Singapore Dollar (SGD)</option>
              <option value="USD">US Dollar (USD)</option>
              <option value="AUD">Australian Dollar (AUD)</option>
              <option value="IDR">Indonesia Rupee (IDR)</option>
              <option value="BND">Brunei Dollar (BND)</option>
              <option value="THB">Thailand Baht (THB)</option>
            </select>                    
          </div>

          <div class="form-group">
            <label for="fees_rate">Rate</label>                    
            <input type="number" class="form-control" id="fees_rate" name="fees_rate" />                    
          </div>

          <div class="form-group">
            <label for="fees_remarks">Remarks</label> 
            <textarea id="fees_remarks" name="fees_remarks" rows="3" class="form-control">
            </textarea>                                    
          </div>

          <div class="box-footer">

          </div>

        </div>
        <div class="modal-footer justify-content-end">
          <button type="button" class="btn btn-default" data-dismiss="modal">CLOSE</button>
          <button type="reset" class="btn btn-danger" name="reset" value="reset">
            <i class="fa fa-refresh"></i> RESET
          </button>
          <input type="submit" class="btn btn-primary" name="add" value="ADD" />
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

</div>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->


<!-- Main Footer -->
<footer class="main-footer">
  <!-- To the right -->
  <div class="float-right d-none d-sm-inline">
    Contact developer : <a class="link" target="_blank" href="https://t.me/atifff">@atifff</a>
  </div>
  <!-- Default to the left -->
  <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
</footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

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

<!-- AdminLTE for demo purposes -->
<!-- <script src="../dist/js/demo.js"></script> -->

<script type="text/javascript">
  $(document).ready(function () {
    var table1 = $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    });

    table1.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

    var table2 = $("#example2").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' }
      ]
    });

    table2.buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');

    var table3 = $("#example3").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      'columnDefs' : [
        //hide the second & fourth column
        { 'visible': false, 'targets': [4,5,6] }
      ],
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });


    table3.buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');

    var table4 = $("#example4").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table4.buttons().container().appendTo('#example4_wrapper .col-md-6:eq(0)');

    var table4a = $("#example4a").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table4a.buttons().container().appendTo('#example4a_wrapper .col-md-6:eq(0)');


    var table5 = $("#example5").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table5.buttons().container().appendTo('#example5_wrapper .col-md-6:eq(0)');

    var table6 = $("#example6").DataTable({
      "responsive": true, "lengthChange": false,"autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ],
      "columns": [
        { "width": "30%" },
        { "width": "10%" },
        { "width": "30%" },
        { "width": "30%" }
      ]
    });

    table6.buttons().container().appendTo('#example6_wrapper .col-md-6:eq(0)');


    var table7 = $("#example7").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table7.buttons().container().appendTo('#example7_wrapper .col-md-6:eq(0)');


    var table8 = $("#example8").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8.buttons().container().appendTo('#example8_wrapper .col-md-6:eq(0)');

    var table8a = $("#example8a").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8a.buttons().container().appendTo('#example8a_wrapper .col-md-6:eq(0)');

    var table8b = $("#example8b").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8b.buttons().container().appendTo('#example8b_wrapper .col-md-6:eq(0)');

    var table8c = $("#example8c").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8c.buttons().container().appendTo('#example8c_wrapper .col-md-6:eq(0)');

    var table8d = $("#example8d").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8d.buttons().container().appendTo('#example8d_wrapper .col-md-6:eq(0)');

    var table8e = $("#example8e").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8e.buttons().container().appendTo('#example8e_wrapper .col-md-6:eq(0)');

    var table8f = $("#example8f").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8f.buttons().container().appendTo('#example8f_wrapper .col-md-6:eq(0)');

    var table8g = $("#example8g").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table8g.buttons().container().appendTo('#example8g_wrapper .col-md-6:eq(0)');

    var table9 = $("#example9").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table9.buttons().container().appendTo('#example9_wrapper .col-md-6:eq(0)');


    var table10 = $("#example10").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table10.buttons().container().appendTo('#example10_wrapper .col-md-6:eq(0)');


    var table11 = $("#example11").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table11.buttons().container().appendTo('#example11_wrapper .col-md-6:eq(0)');

    var table12 = $("#example12").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons" : [
        { extend: 'copy', className: 'btn btn-success' },
        { extend: 'csv', className: 'btn btn-success' },
        { extend: 'excel', className: 'btn btn-success' },
        { extend: 'pdf', className: 'btn btn-success' },
        { extend: 'print', className: 'btn btn-success' },
        { extend: 'colvis', className: 'btn btn-success' }
      ]
    });

    table11.buttons().container().appendTo('#example12_wrapper .col-md-6:eq(0)');


    $('#table2excel').DataTable({
      paging: false,
      "info": false,
      searching: true,
      dom: 'Bfrtip',
      buttons: [
        { extend: 'copy', className: 'btn btn-primary' },
        { extend: 'csv', className: 'btn btn-primary' },
        { extend: 'excel', className: 'btn btn-primary' },
        { extend: 'pdf', className: 'btn btn-primary' },
        { extend: 'print', className: 'btn btn-primary' }
      ]

    });

    $('#table2excel2').DataTable({
      paging: false,
      "info": false,
      searching: false,
      dom: 'Bfrtip',
      buttons: [
        { extend: 'copy', className: 'btn btn-primary ' },
        { extend: 'csv', className: 'btn btn-primary' },
        { extend: 'excel', className: 'btn btn-primary' },
        { extend: 'pdf', className: 'btn btn-primary' },
        { extend: 'print', className: 'btn btn-primary' }
      ]
    });

  });
</script>
</body>
</html>
