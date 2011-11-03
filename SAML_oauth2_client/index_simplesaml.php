<?php
/**
 * @package oauth_client
 */
include_once('../oauth_client/src/OAuth.class.php');
//We start off with loading a file which registers the simpleSAMLphp classes with the autoloader.
require_once('utils/simplesamlphp/lib/_autoload.php');

$content='';
//We select our authentication source:
$sp = 'default-sp-userpass';
$as = new SimpleSAML_Auth_Simple($sp);

// handle logout
if (isset($_REQUEST['logout'])) {
	if ($as->isAuthenticated()) {
		try {
			$as->logout();
		} catch(Exception $e) {
			$content.="<div class='error'>SimpleSAML error: ".$e->getMessage()."</div>";
		}
	} else {
		$content .= "<p>You been logged out.</p>";
		$content .= "<p><a href='?login'>Play again</a>.</p>";
	}

// handle normal flow
} else {

	
	//We then require authentication:
	$as->requireAuth();
	//And print the attributes:
	$attributes = $as->getAttributes();
	error_log(print_r($attributes,true));
	$assertion = $attributes;
	if($assertion=="") {
		$content.="<div class='error'>Access denied.</div>";
	} else {
		$sho=@$assertion['urn:mace:dir:attribute-def:eduPersonScopedAffiliation'][0];
		if (is_null($sho)) $sho = @$assertion['eduPersonScopedAffiliation'][0];
		$content .="<p>Because of your affiliation <b>'".$sho."'</b>, you can access the following services:";
		$client = new OAuth(dirname(__FILE__)."/own_config");
		if(!$client->doOAuthFlow($assertion)){
		    $content.=$client->getError();
		}else{
		    $content.=$client->getResource();
		}        
		$content .= "<p><a href='?logout'>logout</a></p>";
	}
}
include 'html/template.php';
?>  
