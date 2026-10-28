<?php
if (session_status() === PHP_SESSION_NONE)

    session_start();


Class Usersession {

    function getuserName() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $user_name = $user["name"];
                return $user_name;
            }
        }
    }

    function getID() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $user_id = $user["id"];
                return $user_id;
            }
        }
    }

    function getEmail() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $user_email = $user["email"];
                return $user_email;
            }
        }
    }

    function getPhoto() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $user_photo = $user["photo"];
                return $user_photo;
            }
        }
    }

    function getLastSignin() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $last_signin = $user["last_signin"];
                return $last_signin;
            }
        }
    }

    function getCurrentSignin() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $current_signin = $user["current_signin"];
                return $current_signin;
            }
        }
    }

    function getuserlevel_id() {
        if (isset($_SESSION["user"])) {

            $array = array_values($_SESSION["user"]);
            $user_level = $array[0]['level'];

            return $user_level;
        }
    }

    function getuserlevel() {
        if (isset($_SESSION["user"])) {

            $array = array_values($_SESSION["user"]);
            $user_level = $array[0]['level'];

            require_once '../Dbcontroller.php';
            $db_handle = new Dbcontroller();

            $sql_memberlevel = "SELECT jawatan FROM members_role WHERE code='$user_level' ";
            $result_memberlevel = $db_handle->run($sql_memberlevel);

            $member_level_name = mysqli_fetch_array($result_memberlevel);
            return $member_level_name[0];
        }

    }

    function getuserid() {
        if (isset($_SESSION["user"])) {
            foreach ($_SESSION["user"] as $user) {
                $user_id = $user["id"];
                return $user_id;
            }
        }
    }

    function getSession() {
        if (!empty($_SESSION["user"])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

