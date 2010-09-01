<?php
/**
 * @package oauth_client
 */
include_once 'src/OAuth.class.php';
//We start off with loading a file which registers the simpleSAMLphp classes with the autoloader.
require_once('utils/simplesamlphp/lib/_autoload.php');
//We select our authentication source:
$as = new SimpleSAML_Auth_Simple('default-sp');
//We then require authentication:
$as->requireAuth();
//And print the attributes:
$attributes = $as->getAttributes();
error_log(print_r($attributes,true));
$assertion = $attributes;
$content="";
if($assertion=="") {
        $content.="<div class='error'>Usuario no autorizado.</div>";
}else{
        $sho =$assertion['urn:mace:dir:attribute-def:eduPersonScopedAffiliation'][0];
        $content .="<p>Por pertenecer a <b>'".$sho."'</b> puedes acceder a los siguientes servicios:";
        $client = new OAuth(dirname(__FILE__)."/own_config/clientConfig.xml");
        $oauth_as="https://oauth-server.rediris.es/oauth2_09/oauth_as/tokenEndpoint.php";
        $oauth_rs="https://oauth-server.rediris.es/oauth2_09/oauth_server/serverEndpoint.php";
        $client->setAs($oauth_as);
        $client->setRs($oauth_rs);
        if(!$client->doOAuthFlow($assertion)){
            $content.=$client->getError();
        }else{
            $content.=$client->getResource();
        }        
}
include 'html/template.php';
?>  