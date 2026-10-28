<?php
session_start();
require_once '../security.php';
require_once '../Dbcontroller.php';
require_once '../Util.php';
require_once '../Notify.php';

/*
 * FIX M2 — this file had no Usersession include and no gate whatsoever, so an
 * anonymous visitor could insert or delete payment rates. It also built both
 * queries by string interpolation.
 */
require_role([1, 2, 3]);   // super admin, admin, bendahari

$db_handle = new DBController();
$util      = new Util();

//add fees
if (isset($_POST["add"])) {

    csrf_verify();
    require_role([1, 2, 3]);

    $name      = trim(strip_tags((string)($_POST['fees_name'] ?? '')));
    $category  = trim(strip_tags((string)($_POST['fees_category'] ?? '')));
    $dateRaw   = (string)($_POST['fees_date_end'] ?? '');
    $currency  = trim(strip_tags((string)($_POST['fees_currency'] ?? '')));
    $rate      = trim(strip_tags((string)($_POST['fees_rate'] ?? '')));
    $url_link  = trim((string)($_POST['url_link'] ?? ''));
    $remark    = trim(strip_tags((string)($_POST['fees_remarks'] ?? '')));

    $ts = $dateRaw !== '' ? strtotime($dateRaw) : false;
    $date_end = $ts !== false ? date('Y-m-d', $ts) : '';

    if ($name === '' || $rate === '' || $date_end === '') {
        Notify::set("Please fill in the payment name, rate and end date.", "danger");
        $util->redirect("./fees-rate.php");
    }

    // Only http(s) links are ever stored, so a javascript: URL cannot be saved.
    if ($url_link !== '' && !preg_match('#^https?://#i', $url_link)) {
        Notify::set("The payment link must start with http:// or https://.", "danger");
        $util->redirect("./fees-rate.php");
    }

    $ok = $db_handle->prepareAndExecute(
        "INSERT INTO payment_rate (name, category, date_end, currency, rate, link, remark) VALUES (?,?,?,?,?,?,?)",
        [$name, $category, $date_end, $currency, $rate, $url_link, $remark]
    );

    if ($ok) {
        Notify::set("Insert new payment success.", "success");
    } else {
        Notify::set("Insert new payment failed. Please try again and contact developer ASAP.", "danger");
    }
    $util->redirect("./fees-rate.php");
}

/*
 * FIX H6 — deletion was a plain GET with no token, so any page that embedded
 * <img src="…/fees.process.php?delete=1&id=3"> removed a rate.
 */
if (isset($_GET['delete'])) {
    csrf_verify_query();
    require_role([1, 2, 3]);

    $fees_id = (int)($_GET['id'] ?? 0);
    if ($fees_id < 1) {
        Notify::set("Delete payment failed: no rate was specified.", "danger");
        $util->redirect("./fees-rate.php");
    }

    $ok = $db_handle->prepareAndExecute("DELETE FROM payment_rate WHERE id = ?", [$fees_id]);

    if ($ok) {
        Notify::set("Delete payment success.", "success");
    } else {
        Notify::set("Delete payment failed. Please try again and contact developer ASAP.", "danger");
    }
    $util->redirect("./fees-rate.php");
}
