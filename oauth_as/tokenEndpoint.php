<?php
include_once('src/oauthAS.class.php');
// Authorization Server Endpoint
//TODO: Change the location of your Server Configuration
$config_dir = dirname(__FILE__)."/config/";
$as = new oauthAS($config_dir);
//file_put_contents('/Users/kurtiscobainis/Desktop/prueba.txt', apache_request_headers(), FILE_APPEND | LOCK_EX );
$as->manageRequest($_POST);
?>