<?php
include_once('oauth_as/src/oauthAS.class.php');
// Authorization Server Endpoint
//TODO: Change the location of your Server Configuration
$config_dir = dirname(__FILE__)."/own_config/";
$as = new oauthAS($config_dir);
$as->manageRequest($_POST);
?>