<?php

/**
 * @package oauth_client
 */

include_once('../oauth_client/src/OAuth.class.php');
//We start off with loading a file which registers the simpleSAMLphp classes with the autoloader.
require_once('utils/simplesamlphp/lib/_autoload.php');

$content='';

function error($msg) {
  $content.="<div class='error'>SimpleSAML error: ".$e->getMessage()."</div>";
  include 'html/template.php';
  exit(1);
}

// authsource must be selected before we can use SimpleSAMLphp
$sp = null;
$as = null;
if (isset($_REQUEST['authsource'])) {
    $sp = $_REQUEST['authsource'];
    try {
        $as = new SimpleSAML_Auth_Simple($sp);
    } catch(Exception $e) {
        error("SimpleSAML error: ".$e->getMessage());
    }
}

// handle logout
if (isset($_REQUEST['logout']) && !empty($as)) {
    try {
        if ($as->isAuthenticated()) $as->logout();
    } catch(Exception $e) {
        error("SimpleSAML error: ".$e->getMessage());
    }
}

// authsource selection on first entry or logout
if (!isset($_REQUEST['authsource']) || isset($_REQUEST['logout'])) {
    // first visit or just logged out: select authsource
    $authcfg = SimpleSAML_Configuration::getConfig('authsources.php');
    $authsources = $authcfg->getOptions();
    $authsdfl = @$_REQUEST['authsource'];
    $content .= "<p>You are not logged in.</p>\n";
    $content .= "<form action='".$_SERVER['REQUEST_URI']."' method='get'>\n";
    $content .= "  <input type='hidden' name='login' value='1'/>\n";
    $content .= "  Login using <select name='authsource'>\n";
    foreach ($authsources as $as) {
      if (empty($authsdfl) && strncmp($as,"default",7)==0) $authsdfl = $as;
      $opt = ($authsdfl==$as) ? " selected='selected'" : "";
      $content .= "    <option$opt>$as</option>\n";
    }
    $content .= "  </select>\n";
    $content .= "  <input type='submit' name='submit' value='Go'/>\n";
    $content .= "</form>\n";
    
} else {
    // authsource selected: login and show resource
    $as->requireAuth();
    //And print the attributes:
    $attributes = $as->getAttributes();
    error_log(print_r($attributes,true));
    $assertion = $attributes;
    if($assertion=="") {
    	$content.="<div class='error'>Access denied.</div>";
    } else {
    	$client = new OAuth(dirname(__FILE__)."/own_config");
    	if(!$client->doOAuthFlow($assertion)){
    	    $content.="<div class='error'>".$client->getError()."</div>";
    	}else{
    	    $content.="<p>The resource server returned the following message:</p>";
    	    $content.="<p><i>";
    	    $content.=$client->getResource();
    	    $content.="</i></p>";
    	}        
    	$content .= "<p><a href='?logout&amp;authsource=$sp'>logout</a></p>";
    }
}
include 'html/template.php';
?>  
