<?php
session_start();
require_once './Dbcontroller.php';
require_once './Util.php';
//2025
require_once './Notify.php';

$db_handle = new DBController();
$util = new Util();


if(isset($_POST["submit_ex_abstract"])){

    csrf_verify();

    // FIX — read the session before comparing it; the original warned on every
    // logged-out POST.
    require_project();

    $sessionId = (string)$_SESSION["project"];

    /*
     * FIX — the project was selected purely from POST, so any signed-in
     * participant could submit an abstract against another participant's
     * project by editing the hidden id/email fields. The session wins now.
     */
    $project = db_one(
        $db_handle->link(),
        "SELECT id, email, abstract_status FROM project WHERE id = ? AND email = ? LIMIT 1",
        [$sessionId, trim((string)($_POST["email"] ?? ''))]
    );

    if ($project === null) {
        Notify::set("No relevant project found. This is due to technical issue. Please contact the organizer as soon as possible.","danger");
        $util->redirect("./secure.php");
    }

    $id     = (string)$project["id"];
    $status = (string)($project["abstract_status"] ?? '');

    if (in_array($status, ["ACCEPT", "COMPLETE"])) {
        Notify::set("The extended abstract has already been accepted.", "danger");
        $util->redirect("./secure.php");
    }

    if (in_array($status, ["UPLOAD", "CORRECTION"])) {
        Notify::set("The extended abstract is under verification process.", "danger");
        $util->redirect("./secure.php");
    }

    // Allowed file types & MIME types
    /*
     * FIX C2 class — 'application/octet-stream' was in the allowed MIME list as
     * a "fallback", which defeats the content check entirely: any file whose
     * real type is not recognised reports octet-stream and passes. Removed.
     * MIME is probed from content, never taken from $_FILES['type'].
     */
    $allowed_extensions_extended = ['doc', 'docx'];
    $allowed_mime_extended = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
    ];
$allowed_extensions_turnitin = ['pdf'];
$allowed_mime_turnitin = ['application/pdf'];

    // Get file details
$abstract_file = $_FILES['extended_file'];
$turnitin_file = $_FILES['turitin_file'];

$abstract_ext = strtolower(pathinfo($abstract_file['name'], PATHINFO_EXTENSION));
$turnitin_ext = strtolower(pathinfo($turnitin_file['name'], PATHINFO_EXTENSION));

$abstract_mime = mime_content_type($abstract_file['tmp_name']);
$turnitin_mime = mime_content_type($turnitin_file['tmp_name']);

    // Validate file types
if (!in_array($abstract_ext, $allowed_extensions_extended) || !in_array($abstract_mime, $allowed_mime_extended)) {
    Notify::set("Invalid file format for the extended abstract. Please upload a DOC or DOCX file.", "danger");
    $util->redirect("./secure.php");
}

if (!in_array($turnitin_ext, $allowed_extensions_turnitin) || !in_array($turnitin_mime, $allowed_mime_turnitin)) {
    Notify::set("Invalid file format for the Turnitin report. Please upload a PDF file.", "danger");
        //echo $turnitin_ext;
        //echo $turnitin_mime;
    $util->redirect("./secure.php");
}

    //check file size
    //nak ubah boleh kat sini. ver2 nanti set dalam config file.
$max_file_size = "10MB";

if ($_FILES["extended_file"]["size"] > $util->convertToBytes($max_file_size)) {
    Notify::set("The file size is larger than 10MB","danger");
    $util->redirect("./secure.php");
}

if ($_FILES["turitin_file"]["size"] > $util->convertToBytes($max_file_size)) {
  Notify::set("The file size is larger than 10MB","danger");
  $util->redirect("./secure.php");
}

$abstract_temp = explode(".", $_FILES["extended_file"]["name"]);
$abstract_newfilename = $id."-".round(microtime(true)) . '.' . $abstract_ext;
$abstract_targetPath = './upload/abstract/' . $abstract_newfilename;

$turnitin_temp = explode(".", $_FILES["turitin_file"]["name"]);
$turnitin_newfilename = $id."-".round(microtime(true)) . '.' . $turnitin_ext;
$turnitin_targetPath = './upload/turnitin/' . $turnitin_newfilename;

if(move_uploaded_file($_FILES['extended_file']['tmp_name'], $abstract_targetPath)){
    if (move_uploaded_file($_FILES['turitin_file']['tmp_name'], $turnitin_targetPath)) {

        if ($status == "REVIEW" || $status == "CORRECTION_REMARK") {
                    //second time upload
            $sql_extended_abstract = "INSERT INTO project_extended_abstract (project_id, ex_abstract_file, turnitin_file, status) VALUES ('$id','$abstract_newfilename','$turnitin_newfilename','CORRECTION')";

            $sql_abstract = "UPDATE project SET abstract_status='CORRECTION', ex_abstract_file='$abstract_newfilename', turnitin_file='$turnitin_newfilename' WHERE id='$id'";
        }else{
                    //fresh upload
            $sql_extended_abstract = "INSERT INTO project_extended_abstract (project_id, ex_abstract_file, turnitin_file) VALUES ('$id','$abstract_newfilename','$turnitin_newfilename')";

            $sql_abstract = "UPDATE project SET abstract_status='UPLOAD', ex_abstract_file='$abstract_newfilename', turnitin_file='$turnitin_newfilename' WHERE id='$id'";
        }
        $result3 = $db_handle->run($sql_extended_abstract);
        $result2 = $db_handle->run($sql_abstract);
        if($result2 && $result3 ){
            Notify::set("The file was accepted and will be reviewed by organiser.","success");
            $util->redirect("./secure.php");
                    //$util->redirect("./index.php?success=extended_abstract");
        }else{
            Notify::set("Unable to store the new information. Please contact organiser immidiately.","danger");
            $util->redirect("./secure.php");
                    //$util->redirect("./index.php?fail=extended_abstract");
        }
    }else{
        Notify::set("Unable to store the turintin file. Please try again.","danger");
        $util->redirect("./secure.php");
                //$util->redirect("./?fail=file&move=turnitin");
    }
}else{
    Notify::set("Unable to store the extended abstract file. Please try again.","danger");
    $util->redirect("./secure.php");
            //$util->redirect("./?fail=file&move=extended abstract");
}            
}
