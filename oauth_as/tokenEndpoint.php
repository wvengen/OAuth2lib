<?php
require_once('src/oauthAS.class.php');
// Authorization Server Endpoint
$as = new oauthAS();
$as->manageRequest($_POST);
?>