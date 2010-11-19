<?php

/**
 * @package oauth_client
 */
include_once 'utils/papiUtils.php';
include_once 'oauth_client/src/OAuth.class.php';

$assertion_array = $_SESSION['userdata'];
$assertion_string = $_SESSION['userdata']['PAPIAssertion'];

if ($assertion_array['PAPIAuthValue'] == 0 || !isset($_REQUEST['selector_name'])) {
    $content = "<div class='error'>Usuario no autorizado.</div>";
} else {
    $client = new OAuth(dirname(__FILE__)."/own_config/");
    $oauth_as = "https://oauth-server.rediris.es/oauth2lib_svn/oauth2lib/trunk/oauth_as/tokenEndpoint.php";
    $oauth_rs = "https://oauth-server.rediris.es/oauth2lib_svn/oauth2lib/trunk//oauth_server/serverEndpoint.php";
    $client->setAs($oauth_as);
    $client->setRs($oauth_rs);
    $scope = "http://www.rediris.es/sir/api/sps_available.php"."?sho=".$assertion_array['sHO'];
  //    $scope = "http://www.rediris.es/sir/api/sps_available.php"."?sho=unizar.es";
    $client->setScope($scope);
    $client->setBODYResourceRequest();
    $res = $client->doOAuthFlow($assertion_string);
    if (!$res) {
        $content =$client->getError();
    } else {
        $content =$client->getResource();
    }
}
include 'html/template.php';
?>