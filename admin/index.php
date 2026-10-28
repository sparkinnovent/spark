<?php
session_start();
require_once './Usersession.php';

$user = new Usersession();

$session_id = $user->getSession();

if(!$session_id){
	header ("Location: ./login.php");
}else{
	header ("Location: ./secure.php");
}