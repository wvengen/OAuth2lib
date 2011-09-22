<?php
include_once 'src/oauthRS.class.php';
/**
 * Request resource Endpoint
 */
//TODO: Change the location of your Server Configuration
$config_dir = dirname(__FILE__)."/config/";
$rs = new oauthRS($config_dir);
$rs->manageRequest();
?>