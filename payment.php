<?php
session_start();
require_once './security.php';
require_once './Dbcontroller.php';
require_once './Util.php';
require_once './Notify.php';

$db_handle = new DBController();
$util      = new Util();

// FIX — the handler previously ran with no session check at all.
require_project();

$id = (string)($_SESSION['project'] ?? '');

if (isset($_POST["submit_payment"])) {

    csrf_verify();
    require_project();

    $email          = trim((string)($_POST["email"] ?? ''));
    $payment_date   = (string)($_POST["payment_date"] ?? '');
    $payment_method = (int)($_POST["payment_method"] ?? 0);

    // Confirm the session project matches the submitted email.
    $row = db_one($db_handle->link(),
        "SELECT payment_status FROM project WHERE id = ? AND email = ?",
        [$id, $email]
    );

    if ($row === null) {
        Notify::set('project_not_found', 'danger');
        $util->redirect("./secure.php");
    }

    $status = (string)($row["payment_status"] ?? '');
    if ($status === "ACCEPT" || $status === "ACCEPT_RECEIPT") {
        Notify::set('Payment already made', 'danger');
        $util->redirect("./secure.php");
    }

    /*
     * FIX C2 — three defects removed:
     *   1. $_FILES['type'] is client supplied. save_upload() probes the real
     *      MIME from the file content instead.
     *   2. The saved extension came from the user's filename, so "shell.php"
     *      was stored as .php and executed. save_upload() derives the extension
     *      from a whitelist and generates a random name.
     *   3. The target directory had no execution guard. See the .htaccess added
     *      to each upload directory alongside this patch.
     */
    if (!isset($_FILES["payment_slip"]) || !is_array($_FILES["payment_slip"])) {
        Notify::set('No file was received. Please choose your proof of payment.', 'danger');
        $util->redirect("./secure.php");
    }

    $upload = save_upload(
        $_FILES["payment_slip"],
        __DIR__ . '/upload/payment/',
        ['pdf', 'jpg', 'jpeg', 'png'],
        10 * 1024 * 1024
    );

    if (!$upload['ok']) {
        Notify::set('Unable to process the file: ' . $upload['error'], 'danger');
        $util->redirect("./secure.php");
    }

    $newfilename = $upload['name'];
    $ext = strtolower(pathinfo($newfilename, PATHINFO_EXTENSION));
    $payment_slip_type = ($ext === 'pdf') ? "PDF" : "IMAGE";

    $rate = db_value($db_handle->link(),
        "SELECT rate FROM payment_rate WHERE id = ?",
        [$payment_method]
    );
    if ($rate === null) {
        @unlink(__DIR__ . '/upload/payment/' . $newfilename);
        Notify::set('Please select a valid payment method.', 'danger');
        $util->redirect("./secure.php");
    }

    $ok = $db_handle->prepareAndExecute(
        "INSERT INTO project_payment (project_id, payment_date, payment_proof, payment_type, payment_method)
         VALUES (?,?,?,?,?)",
        [$id, $payment_date, $newfilename, $payment_slip_type, $payment_method]
    );

    if (!$ok) {
        @unlink(__DIR__ . '/upload/payment/' . $newfilename);
        Notify::set('Unable to update status.', 'danger');
        $util->redirect("./secure.php");
    }

    $stamp = now_my();
    $ok2 = $db_handle->prepareAndExecute(
        "UPDATE project SET payment_status='UPLOAD', fees=? WHERE id=?",
        [(string)$rate, $id]
    );
    $ok3 = $db_handle->prepareAndExecute(
        "UPDATE project_timeline SET payment=? WHERE project_id=?",
        [$stamp, $id]
    );

    if ($ok2 && $ok3) {
        Notify::set('Proof of payment have been recorded. Thank you.', 'success');
    } else {
        Notify::set('Unable to update the database.', 'danger');
    }
    $util->redirect("./secure.php");
}
