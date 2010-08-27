<?php
require_once('oauth_as/src/oauthAS.class.php');
// Authorization Server Endpoint
$as = new oauthAS();
$as->manageRequest($_POST);
?>