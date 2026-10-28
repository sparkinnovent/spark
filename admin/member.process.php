<?php
session_start();
require_once '../security.php';
require_once '../Dbcontroller.php';
require_once './Usersession.php';
require_once '../Util.php';
require_once '../Notify.php';

/*
 * FIX C1 — this file previously required Usersession.php but never called
 * getSession(), so an anonymous POST could create an account with
 * member_level=1 (super admin). Every handler below now refuses to run
 * without an authenticated administrator, and only admin/super-admin may
 * create or re-role accounts.
 */
require_admin();

$db_handle = new DBController();
$util      = new Util();

// Roles seeded in members_role: 1 super admin, 2 admin, 3 bendahari, 4 sijil, 5 ajk
const MEMBER_MANAGE_ROLES = [1, 2];

//add member using jquery
if (isset($_POST["add_j"])) {

    csrf_verify();
    require_role(MEMBER_MANAGE_ROLES);

    if (isset($_GET["p"])) {
        $page = $_GET["p"];
    }

    $name     = trim(strip_tags((string)($_POST['member_name'] ?? '')));
    // FIX H2 — never strip_tags or escape a password. It is hashed, not queried.
    $password = (string)($_POST['member_password'] ?? '');
    $role     = (int)($_POST['member_role'] ?? 0);
    $email    = trim((string)($_POST['member_email'] ?? ''));

    if ($name === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $role < 1 || $role > 5) {
        echo 'failed';
        exit;
    }

    // Only a super admin may mint another super admin.
    if ($role === 1 && !is_super_admin()) {
        http_response_code(403);
        echo 'failed';
        exit;
    }

    $result_exist = $db_handle->getMemberByUsername($name);

    if (!$result_exist) {
        $insert_result = $db_handle->insertMember($name, $password, $role, $email);

        if ($insert_result instanceof mysqli_stmt && $insert_result->affected_rows === 1) {
            echo 'ok';
        } else {
            echo 'failed';
        }
    } else {
        echo 'failed';
    }
    exit;
}



//add member
if(isset($_POST["add"])){

    csrf_verify();
    require_role(MEMBER_MANAGE_ROLES);

    if(isset($_GET["p"])){
        $page = $_GET["p"];
    }

    $name     = trim(strip_tags((string)($_POST['member_name'] ?? '')));
    $password = (string)($_POST['member_password'] ?? '');
    $role     = (int)($_POST['member_role'] ?? 0);
    $email    = trim((string)($_POST['member_email'] ?? ''));

    if ($name === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $role < 1 || $role > 5) {
        $util->redirect("./dashboard.php?fail=members");
    }
    if ($role === 1 && !is_super_admin()) {
        http_response_code(403);
        exit('You do not have permission to create a super admin.');
    }

    $result_exist = $db_handle->getMemberByUsername($name);

    if(!$result_exist){
        $insert_result = $db_handle->insertMember($name, $password, $role, $email);

        if($insert_result instanceof mysqli_stmt && $insert_result->affected_rows == 1){
         $util->redirect("./dashboard.php?success=members");
     }
     else{
        $util->redirect("./dashboard.php?fail=members");
    }
}else{
    $util->redirect("./dashboard.php?fail=members_exist");

}
}

//update info
if(isset($_POST["update_pwd"])){

    csrf_verify();
    require_admin();

    if(isset($_GET["p"])){
        $page = $_GET["p"];
    }

    $id = (int)($_SESSION["member_id"] ?? 0);
    if ($id < 1) {
        $util->redirect("./login.php");
    }

    $name     = trim(strip_tags((string)($_POST['member_name'] ?? '')));
    $password = (string)($_POST['member_password'] ?? '');
    $email    = trim((string)($_POST['member_email'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Notify::set("All required fields must be filled with a valid email.", "danger");
        $util->redirect("./profile-update.php");
    }

    // FIX H2 — hash the raw password; it is bound, never escaped.
    if ($password === '') {
        $result = $db_handle->prepareAndExecute(
            "UPDATE members SET member_name=?, member_email=? WHERE member_id=?",
            [$name, $email, $id]
        );
    } else {
        $result = $db_handle->prepareAndExecute(
            "UPDATE members SET member_name=?, member_email=?, member_password=? WHERE member_id=?",
            [$name, $email, password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    if($result !== false){
        $util->redirect("./profile-update.php?success");
    }else{
        $util->redirect("./profile-update.php?failinsert");
    }
}

//update profile
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {

    csrf_verify();
    require_admin();

    $me = (int)($_SESSION["member_id"] ?? 0);
    if ($me < 1) {
        $util->redirect("./login.php");
    }

    /*
     * FIX — IDOR + privilege escalation. The original took $user_id straight
     * from ?id= and $role from POST, so any logged-in user could rewrite any
     * other account (including their own) to member_level=1.
     * Now: you may only target yourself unless you are a super admin, and only
     * a super admin may hand out the super admin role.
     */
    $target_id = $me;
    if (isset($_GET["id"])) {
        $requested = (int)$_GET["id"];
        if ($requested !== $me) {
            if (!is_super_admin()) {
                http_response_code(403);
                exit('You may only edit your own profile.');
            }
            $target_id = $requested;
        }
    }

    $origin = isset($_GET["origin"]) ? (string)$_GET["origin"] : trim((string)($_POST['origin'] ?? ''));
    // Only allow same-directory relative redirects.
    if ($origin === '' || preg_match('#^(https?:)?//#i', $origin)) {
        $origin = './profile-update.php';
    }

    $username = trim(strip_tags((string)($_POST['username'] ?? '')));
    $email    = trim((string)($_POST['email'] ?? ''));
    $role     = (int)($_POST['role'] ?? 0);
    $password = (string)($_POST['password'] ?? '');
    $repassword = (string)($_POST['repassword'] ?? '');
    $profile_photo = $_FILES['profile_photo'] ?? null;

    if ($username === '' || $email === '' || $role < 1 || $role > 5) {
        Notify::set("All required fields must be filled.", "danger");
        $util->redirect($origin);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Notify::set("Invalid email format.", "danger");
        $util->redirect($origin);
    }
    if (!empty($password) && $password !== $repassword) {
        Notify::set("Passwords do not match.", "danger");
        $util->redirect($origin);
    }
    if ($role === 1 && !is_super_admin()) {
        http_response_code(403);
        exit('You do not have permission to grant the super admin role.');
    }

    $sql = "UPDATE members SET member_name=?, member_email=?, member_level=? WHERE member_id=?";
    $params = [$username, $email, $role, $target_id];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE members SET member_name=?, member_email=?, member_level=?, member_password=? WHERE member_id=?";
        $params = [$username, $email, $role, $hashed_password, $target_id];
    }

    // Process profile photo upload
    if (is_array($profile_photo) && ($profile_photo['size'] ?? 0) > 0) {
        $upload = save_upload(
            $profile_photo,
            __DIR__ . '/dist/',
            ['jpg', 'jpeg', 'png', 'gif'],
            2 * 1024 * 1024
        );
        if (!$upload['ok']) {
            Notify::set("Invalid photo: " . $upload['error'], "danger");
            $util->redirect($origin);
        }
        $stored = $upload['name'];

        $sql = "UPDATE members SET member_name=?, member_email=?, member_level=?, photo=? WHERE member_id=?";
        $params = [$username, $email, $role, $stored, $target_id];

        if (!empty($password)) {
            $sql = "UPDATE members SET member_name=?, member_email=?, member_level=?, member_password=?, photo=? WHERE member_id=?";
            $params = [$username, $email, $role, $hashed_password, $stored, $target_id];
        }
    }

    $affected_rows = $db_handle->prepareAndExecute($sql, $params);

    if ($affected_rows !== false) {
        Notify::set("Profile updated successfully!", "success");
        $util->redirect($origin);
    } else {
        Notify::set("Error updating profile. No changes made.", "danger");
        $util->redirect($origin);
    }
}








