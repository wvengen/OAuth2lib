<?php
require_once 'oauth_server/src/oauthRS.class.php';
//require_once 'src/oauthRS.class.php';
/**
 * Request resource Endpoint
 */
$rs = new oauthRS();
$rs->manageRequest();
?>