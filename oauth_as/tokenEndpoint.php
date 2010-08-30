<?php
//require_once('oauth_as/src/oauthAS.class.php');
require_once('src/oauthAS.class.php');
// Authorization Server Endpoint
$as = new oauthAS();
$as->manageRequest($_POST);
?>