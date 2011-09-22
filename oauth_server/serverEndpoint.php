<?php
include_once 'oauth_server/src/oauthRS.class.php';
/**
 * Request resource Endpoint
 */
//TODO: Change the location of your Server Configuration
$config_dir = dirname(__FILE__)."/own_config/";
$rs = new oauthRS($config_dir);
$rs->manageRequest();
?>