<?php
session_start();
if(!isset($_SESSION['userdata'])){
	include "phpPoA/php/PoA.php";
	$poa = new autoPoA('oauth');
	$userData = $poa->check_Access();
	$_SESSION['userdata'] = $userData;
}
?>