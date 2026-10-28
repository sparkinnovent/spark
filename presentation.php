<?php
session_start();
require_once './security.php';
require_once './Dbcontroller.php';
require_once './Util.php';
//2025
require_once './Notify.php';

$db_handle = new DBController();
$util = new Util();

if (isset($_POST["submit_video"])) {

    csrf_verify();

    // FIX — $_SESSION['project'] was read and cleaned before being tested,
    // warning on every logged-out POST.
    require_project();
    $id = (string)$_SESSION['project'];

    $email = trim((string)($_POST["email"] ?? ''));

    /*
     * FIX C2 class — the presentation URL is echoed into an <a href> and into
     * an email body. Only http(s) is accepted, so a javascript: or data: URL
     * cannot be stored.
     */
    $presentation_video = trim((string)($_POST["video_url"] ?? ''));
    if ($presentation_video !== '' && !preg_match('#^https?://#i', $presentation_video)) {
        Notify::set("Error! The presentation URL must start with http:// or https://.","danger");
        $util->redirect("./secure.php");
    }

    //check if the user is valid or not
    $project = db_one(
        $db_handle->link(),
        "SELECT presentation_status FROM project WHERE id = ? AND email = ? LIMIT 1",
        [$id, $email]
    );
    if ($project === null) {
        Notify::set("Error! not project found!","danger");
        $util->redirect("./secure.php");
    }

    $presentation_status = (string)($project["presentation_status"] ?? '');
    if ($presentation_status == "ACCEPT") {
        Notify::set("Error! The Presentation URL already recorded.","warning");
        $util->redirect("./secure.php");
    }

    //check dah pernah hantar belum
    $existing = db_one(
        $db_handle->link(),
        "SELECT id FROM project_presentation WHERE project_id = ? LIMIT 1",
        [$id]
    );
    if ($existing !== null) {
        $currentDateTime = now_my();
        $presentation_id = (int)$existing["id"];
        //update the column only.
       $sql_insert = "UPDATE project_presentation SET video_link='$presentation_video', recorded='$currentDateTime' WHERE id='$presentation_id'";
    }else{
        $sql_insert = "INSERT INTO project_presentation (project_id, video_link) VALUES ('$id','$presentation_video')";
    }

    //insert project_presentation
    
    $result_insert = $db_handle->run($sql_insert);
    if (!$result_insert) {
        Notify::set("Error! Unable to add or update presentation URL. This is technical error. Please report to us immediately.","danger");
        $util->redirect("./secure.php");

    }else{
        //update table project
        $sql_update_project = "UPDATE project SET presentation_status = 'UPLOAD' WHERE id = '$id' ";
        $result_update_project = $db_handle->run($sql_update_project);

        //update project_timeline 2025
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $currentDateTime = date('Y-m-d H:i:s');
        $sql_timeline = "UPDATE project_timeline SET presentation ='$currentDateTime' WHERE project_id='$id'";
        $result_timeline  = $db_handle->run($sql_timeline);

        if (!$result_update_project) {
            $sql_delete = "DELETE FROM project_presentation WHERE project_id = '$id'";
            $result_delete = $db_handle->run($sql_delete);
             Notify::set("Error! Unable to update table project. The last insert rolled back.","danger");
            $util->redirect("./secure.php");
        }
        else{
             Notify::set("Success! Your presentation Video have been recorded. Administrator will review the video for further process.","success");
            $util->redirect("./secure.php");
        }
    }
}

/*
 * REMOVED — the "submit_video_2023" handler.
 *
 * It took both the project id and the email from POST with no session check and
 * no ownership test, then ran:
 *     UPDATE project SET video_link='$presentation_video', status='PRESENT' WHERE id='$id'
 * so an anonymous request could overwrite any project's video link and force its
 * status to PRESENT. It is the 2023 portal flow; the 2025 flow above replaced it.
 */
