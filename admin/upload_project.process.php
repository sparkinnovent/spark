<?php
session_start();
require_once '../security.php';
require_once '../Dbcontroller.php';
require_once './Usersession.php';
require_once '../Util.php';
require_once '../vendor/autoload.php';
require_once '../Notify.php';

$db_handle = new DBController();
$user = new Usersession();
$util = new Util();

/*
 * FIX H4 — the gate existed but the redirect was commented out:
 *     if (!$login) { // $util->gotohome(); }
 * so the roster import form and its POST handler were public.
 */
require_role([1, 2]);

?>
<!--html -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>GDIID | ADMIN</title>
    <?php include './header-style.php'; ?>

</head>
<!-- ADD THE CLASS layout-top-nav TO REMOVE THE SIDEBAR. -->
<body class="layout-top-nav skin-red">
    <div class="wrapper">
        <?php include './nav.php';?>
        <div class="content-wrapper">
            <section class="content">
                <?php
                if(isset($_POST["import"])){

                    csrf_verify();
                    require_role([1, 2]);

                    $uitmkt = false;
                    if (isset($_GET["cat"])) {
                        if ($_GET['cat'] == "uitmkt") {
                            $uitmkt = true;
                        }
                    }

                    /*
                     * FIX C2 class — $_FILES['type'] is client supplied and the
                     * saved extension came from the user's filename. A file
                     * named "xlsx.php" with a spoofed MIME was stored and
                     * executed. save_upload() probes real content and picks the
                     * name itself.
                     */
                    if (!isset($_FILES["file"]) || !is_array($_FILES["file"])) {
                        Notify::set('No file was received.', 'danger');
                        $util->redirect("./participant-upload.php");
                    }

                    $upload = save_upload(
                        $_FILES["file"],
                        __DIR__ . '/upload/project2025/',
                        ['xls', 'xlsx'],
                        20 * 1024 * 1024
                    );
                    if (!$upload['ok']) {
                        Notify::set('Unable to accept the spreadsheet: ' . $upload['error'], 'danger');
                        $util->redirect("./participant-upload.php");
                    }
                    $newfilename = $upload['name'];
                    $targetPath = __DIR__ . '/upload/project2025/' . $newfilename;

                    try {
// FIX — uniqid() is time-based and guessable; a stranger could predict a
// batch id and then delete someone else's import.
$upload_id = secure_token(16);

$uploader_id = (int)($_SESSION['member_id'] ?? 0);

//update 2025 - unicode
$db_handle->setCharset("utf8mb4");

//new phpspreadsheet gdiid2023
$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadSheet = $Reader->load($targetPath);
$excelSheet = $spreadSheet->getActiveSheet();
// update 2025: resolve masalah hyperlink sebelum ni
$sheetCount = $excelSheet->getHighestRow(); // Get the total number of rows
// $spreadSheetAry = $excelSheet->toArray();
// $sheetCount = count($spreadSheetAry);
//foreach($spreadSheetAry as $key => $Row) {
for ($row = 2; $row <= $sheetCount; $row++) { // Start from row 2 to skip the header
    //if ($key > 0) {
    // skip the first row
    $reg_datetime = NULL;
    $cat_iid = NULL;
    $title = NULL;
    $email = NULL;
    //first author
    $author1_name = NULL;
    $author1_email = NULL;
    $author1_mobileno = NULL;
    $author1_organisation = NULL;
    $author1_country = NULL;
    $author1_proof_student = NULL;
    //2nd author
    $author2_name = NULL;
    $author2_email = NULL;
    $author2_mobileno = NULL;
    $author2_organisation = NULL;
    $author2_country = NULL;
    $author2_role = NULL;
    //3rd author
    $author3_name = NULL;
    $author3_email = NULL;
    $author3_mobileno = NULL;
    $author3_organisation = NULL;
    $author3_country = NULL;
    $author3_role = NULL;
    //4th author
    $author4_name = NULL;
    $author4_email = NULL;
    $author4_mobileno = NULL;
    $author4_organisation = NULL;
    $author4_country = NULL;
    $author4_role = NULL;
    //5th author
    $author5_name = NULL;
    $author5_email = NULL;
    $author5_mobileno = NULL;
    $author5_organisation = NULL;
    $author5_country = NULL;
    $author5_role = NULL;
    $abstract_link = NULL;

        //if ($Row[0]!= NULL) {

    // Read cells by column and row
    $cellValue = $excelSheet->getCell('A' . $row)->getValue(); // Get the raw cell value
    $cellFormat = $excelSheet->getCell('A' . $row)->getFormattedValue(); // Get formatted value (human-readable)

    // Check if the value is numeric (Excel's serialized date-time format)
    if (is_numeric($cellValue)) {
        // Convert Excel's date value to a Unix timestamp and format to MySQL datetime
        $reg_datetime = date("Y-m-d H:i:s", \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cellValue));
    } else {
        // Treat it as a string and parse it using strtotime
        $reg_datetime = date("Y-m-d H:i:s", strtotime($cellValue));
    }

    $cat_iid = $excelSheet->getCell('B' . $row)->getValue();
    $title = $db_handle->clean($excelSheet->getCell('C' . $row)->getValue());
    $email = $db_handle->clean(strip_tags($excelSheet->getCell('D' . $row)->getValue()));

        // Author 1 information
    $author1_name = strtoupper($db_handle->clean($excelSheet->getCell('E' . $row)->getValue()));
    $author1_email = $db_handle->clean(strip_tags($excelSheet->getCell('F' . $row)->getValue()));
    $author1_mobileno = $db_handle->clean(strip_tags($excelSheet->getCell('G' . $row)->getValue()));
    $author1_organisation = strtoupper($db_handle->clean($excelSheet->getCell('H' . $row)->getValue()));
    $author1_country = $db_handle->clean($excelSheet->getCell('I' . $row)->getValue());
    $author1_proof_student = $db_handle->clean($excelSheet->getCell('J' . $row)->getValue());

        // Handle Category A logic
    $searchWord = "Category A";
    $cat_iid = (strpos($cat_iid, $searchWord) !== false) ? "A" : "B";

        // Abstract Link (with hyperlink handling)
    $abstractCell = $excelSheet->getCell('K' . $row);
        $abstract_link = $abstractCell->getHyperlink()->getUrl(); // Extract the hyperlink URL
        if (empty($abstract_link)) {
            // Fallback to visible value if no hyperlink exists
            $abstract_link = $db_handle->clean($abstractCell->getValue());
        }
        //update 2025 - sekalikan dengan uitmkt. cuma kita true je column uitmkt
        if ($uitmkt == true) {
            $sql_project = "INSERT INTO project (reg_datetime, email, organisation, country, contact_no, cat_iid, title, upload_id, uploader_id,student_card_link, abstract_link, payment_status, uitmctkt) VALUES ('$reg_datetime','$email','$author1_organisation','$author1_country', '$author1_mobileno', '$cat_iid','$title','$upload_id','$uploader_id', '$author1_proof_student','$abstract_link', 'UNPAID', 1)";
        }else{
            $sql_project = "INSERT INTO project (reg_datetime, email, organisation, country, contact_no, cat_iid, title, upload_id, uploader_id,student_card_link, abstract_link, payment_status) VALUES ('$reg_datetime','$email','$author1_organisation','$author1_country', '$author1_mobileno', '$cat_iid','$title','$upload_id','$uploader_id', '$author1_proof_student','$abstract_link', 'UNPAID')";
        }
        
            //echo $sql_project;                                

        $result_project = $db_handle->run($sql_project);


        if (!$result_project) {
            Notify::set('Unable to set the new data! Please contact system developer ASAP. ', 'danger');
            $util->redirect("./participant-upload.php");
               // echo $sql_project;
        }

            //get_last_insert_id project tadi
        $project_id = $db_handle->getLastInsertID();

            //add to timeline - GDIID2025
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $currentDateTime = date('Y-m-d H:i:s');
        $sql_timeline = "INSERT INTO project_timeline (project_id, register) VALUES ('$project_id','$currentDateTime')";
        $result_timeline = $db_handle->run($sql_timeline);

        if (!$result_timeline) {
            Notify::set('Unable to set the timeline record for the project. Please contact system developer ASAP. ', 'error');
            $util->redirect("./participant-upload.php");
                //echo $sql_project;
        }


            //first author
        $sql_author1 = "INSERT INTO project_member (project_id, position, name, email, contact_no, organisation, country, role, upload_id) VALUES ('$project_id','1','$author1_name','$author1_email','$author1_mobileno','$author1_organisation','$author1_country','LEADER','$upload_id')";
        $result_author1 = $db_handle->run($sql_author1);

            // Author 2 information
        $author2_name = $db_handle->clean($excelSheet->getCell('L' . $row)->getValue());
        if (!empty($author2_name)) {
            $author2_name = strtoupper($author2_name);
            $author2_email = $db_handle->clean(strip_tags($excelSheet->getCell('M' . $row)->getValue()));
            $author2_organisation = $db_handle->clean($excelSheet->getCell('N' . $row)->getValue());
            $author2_organisation = strtoupper($author2_organisation);
            $author2_country = $db_handle->clean($excelSheet->getCell('O' . $row)->getValue());
            $author2_role = $db_handle->clean(strip_tags($excelSheet->getCell('P' . $row)->getValue()));

            $sql_author2 = "INSERT INTO project_member (project_id, position, name, email, organisation, country, role, upload_id) VALUES ('$project_id', '2', '$author2_name', '$author2_email', '$author2_organisation', '$author2_country', '$author2_role', '$upload_id')";
            $result_author2 = $db_handle->run($sql_author2);
        }

            //3rd author
            $author3_name = $db_handle->clean($excelSheet->getCell('Q' . $row)->getValue()); //Q
            if ($author3_name != "") {
                $author3_name = strtoupper($author3_name);
                $author3_email = $db_handle->clean(strip_tags($excelSheet->getCell('R' . $row)->getValue())); //R
                //$author3_mobileno = $db_handle->clean(strip_tags($Row[19]));
                $author3_organisation = $db_handle->clean($excelSheet->getCell('S' . $row)->getValue()); //S
                $author3_organisation = strtoupper($author1_organisation);
                $author3_country = $db_handle->clean($excelSheet->getCell('T' . $row)->getValue()); //T
                $author3_role = $db_handle->clean(strip_tags($excelSheet->getCell('U' . $row)->getValue())); //U


                $sql_author3 = "INSERT INTO project_member (project_id, position, name, email, organisation, country, role, upload_id) VALUES ('$project_id','3','$author3_name','$author3_email','$author3_organisation','$author3_country','$author3_role','$upload_id')";
                $result_author3 = $db_handle->run($sql_author3);
            }

            //4th author
            $author4_name = $db_handle->clean($excelSheet->getCell('V' . $row)->getValue()); //V
            if ($author4_name != "") {
                $author4_name = strtoupper($author4_name);
                $author4_email = $db_handle->clean(strip_tags($excelSheet->getCell('W' . $row)->getValue())); //W
                //$author4_mobileno = $db_handle->clean(strip_tags($Row[23]));
                $author4_organisation = $db_handle->clean($excelSheet->getCell('X' . $row)->getValue()); //X
                $author4_organisation = strtoupper($author4_organisation);
                $author4_country = $db_handle->clean($excelSheet->getCell('Y' . $row)->getValue()); //Y
                $author4_role = $db_handle->clean(strip_tags($excelSheet->getCell('Z' . $row)->getValue())); //Z

                $sql_author4 = "INSERT INTO project_member (project_id, position, name, email, organisation, country, role, upload_id) VALUES ('$project_id','4','$author4_name','$author4_email','$author4_organisation','$author4_country','$author4_role','$upload_id')";
                $result_author4 = $db_handle->run($sql_author4);
            }

            //5th author
            $author5_name = $db_handle->clean($excelSheet->getCell('AA' . $row)->getValue()); //AA
            if ($author5_name != "") {
                $author5_name = strtoupper($author5_name);
                $author5_email = $db_handle->clean(strip_tags($excelSheet->getCell('AB' . $row)->getValue())); //AB
                // $author5_mobileno = $db_handle->clean(strip_tags($Row[31]));
                $author5_organisation = $db_handle->clean($excelSheet->getCell('AC' . $row)->getValue()); //AC
                $author5_organisation = strtoupper($author5_organisation);
                $author5_country = $db_handle->clean($excelSheet->getCell('AD' . $row)->getValue()); //AD
                $author5_role = $db_handle->clean(strip_tags($excelSheet->getCell('AE' . $row)->getValue())); //AE

                $sql_author5 = "INSERT INTO project_member (project_id, position, name, email, organisation, country, role, upload_id) VALUES ('$project_id','5','$author5_name','$author5_email','$author5_organisation','$author5_country','$author5_role','$upload_id')";
                $result_author5 = $db_handle->run($sql_author5);
            } //end of if ($author5_name != "") {
//    } // end of if ($value[0]!= NULL) {
//} // end of if ($key > 0) {
}//end of for()
} catch (Exception $E) {
    echo $E->getMessage();
    echo "crtical error please contact developer ASAP<br>";
    echo "please screen capture this page now";
}
}
?>
<div class="card card-primary card-outline">
    <div class="card-header with-border">
      <h3 class="card-title">Uploaded Project Progress</h3>
  </div>
  <div class="card-body">
    <table id="example" class="display nowrap">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Organisation</th>
                <th>country</th>
                <th>cat iid</th>
                <th>title</th>
                <th>status</th>
                <th>student status</th>
                <th>upload timestamp</th>
                <th>uploader id</th>
                <th>student card link</th>
                <th>abstract link</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_new = "SELECT * FROM project WHERE upload_id='$upload_id'";
            $result_new = $db_handle->run($sql_new);
            while($row_new = mysqli_fetch_assoc($result_new)){
                $id = $row_new["id"];
                $email = $row_new["email"];                        
                $org = $row_new["organisation"];                        
                $country = $row_new["country"];                        
                $cat_iid = $row_new["cat_iid"];                       
                $title = $row_new["title"];
                $status = $row_new["status"];
                $student_status = $row_new["student_status"];
                $upload_timestamp = $row_new["upload__timestamp"];
                $uploader_id = $row_new["uploader_id"];
                $student_card_link = $row_new["student_card_link"];
                $abstract_link = $row_new["abstract_link"];
                ?>
                <tr>
                    <td><?php echo $id;?></td>
                    <td><?php echo $email;?></td>
                    <td><?php echo $org;?></td>
                    <td><?php echo $country;?></td>
                    <td><?php echo $cat_iid;?></td>
                    <td><?php echo $title;?></td>
                    <td><?php echo $status;?></td>
                    <td><?php echo $student_status;?></td>
                    <td><?php echo $upload_timestamp;?></td>
                    <td><?php echo $uploader_id;?></td>
                    <td><?php echo $student_card_link;?></td>
                    <td><?php echo $abstract_link;?></td>   
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <p>Please check all the information. If all information are correct please press ok. If not, please press cancel. </p>
    <a href="./participant-upload.php?success=upload" class="btn btn-block btn-success">OK</a>
    <form method="post" action="./deleteUpload.php" onsubmit="return confirm('Discard this upload batch?');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo e($upload_id); ?>">
        <button type="submit" class="btn btn-block btn-danger">CANCEL</button>
    </form>
</div>
<!-- /.box-body -->
        <!-- <div class="box-footer">
          Footer
      </div> -->
      <!-- /.box-footer-->
  </div>


</section>
</div>
</div>
<!-- </div> -->
<<!-- REQUIRED SCRIPTS -->

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
    $(document).ready(function() {
        $('#example').DataTable( {
            "scrollX": true
        } );
    } );
</script>
</body>
