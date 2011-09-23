<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);
include 'oauth_client/src/OAuth.class.php';
include 'oauth_server/src/ResServerJWT.php';
include 'PoA.php';

    $poa = new PoA('samples', '/Users/kurtiscobainis/Sites/html/phpPoA-2.4/samples/PoA.conf.php');
    $auth = $poa->authenticate();
    $attrs = $poa->getAttributes();
//    echo '<pre>';
//    print_r($attrs);
//    echo '</pre><hr>';
    echo '<h3><hr>PAPI:<br></h3>';
    $filePAPI = dirname(dirname(__FILE__)) . '/trunk/oauth_client/config/clientConfig.xml';
    $oauthPAPI = new OAuth($filePAPI);
    //$oauthclientPAPI = $oauthPAPI->doOAuthFlow('sPUC=urn:mace:terena.org:schac:personalUniqueCode:es:rediris:sir:mbid:{md5}8bd4ef2432dc88224b13b8d9d5e2377a,cn=Luis Javier Gomez Santana,mail=luis.gomez@rediris.es,uid=luisja,ePTI=13cbd85921629eb77b8d266ff6f5a414,ePA=staff,sHO=rediris.es,ePE=|urn:mace:dir:entitlement:common-lib-terms');
    $oauthclientPAPI = $oauthPAPI->doOAuthFlow($attrs);
    if (!$oauthclientPAPI) {
        $contentPAPI = $oauthPAPI->getError();
        echo $contentPAPI;
        die();
    }else{
        $contentPAPI = $oauthPAPI->getResource();
    }
    //$result = json_decode($content, true);
    //var_dump($result);
    //$image = $result['data']['image'];
    //header("Content-Type: ".$result['data']['mime']); 
    $tokenPAPI = $oauthclientPAPI->getAccess_token();
    


    echo '<b>TOKEN:</b>' . $tokenPAPI . '<br>';

    $jwt_decodedPAPI = new ResServerJWT("/Users/kurtiscobainis/Sites/html/pruebas/gn3-sts.crt");
    echo '<b><br>TOKEN DECODED: </b>';
    echo '<pre>';
    print_r($jwt_decodedPAPI->decode($tokenPAPI));
    echo '</pre>';
    echo '<br>';
    echo '<b>RESOURCE:</b> ' . $contentPAPI . '<br>';




    echo '<h3><hr>SAML2:<br></h3>';
    $fileSAML = dirname(dirname(__FILE__)) . '/trunk/oauth_client/config/clientConfigSAML.xml';
    $oauthSAML = new OAuth($fileSAML);
    $oauthSAML->setSAML2AssertionType();
    $assertionSAML = array('urn:mace:dir:attribute-def:eduPersonScopedAffiliation' => 'rediris.es',
                            'mail' => 'luis.gomez@rediris.es');
    $oauthclientSAML = $oauthSAML->doOAuthFlow($assertionSAML);
    if (!$oauthclientSAML) {
        $contentSAML = $oauthSAML->getError();
        echo $contentSAML;
        die();
    }else{
        $contentSAML = $oauthSAML->getResource();
    }
    //$result = json_decode($content, true);
    //var_dump($result);
    //$image = $result['data']['image'];
    //header("Content-Type: ".$result['data']['mime']);
    $tokenSAML = $oauthclientSAML->getAccess_token();
    echo '<b>TOKEN:</b>' . $tokenSAML  . '<br>';

    $jwt_decodedSAML = new ResServerJWT("/Users/kurtiscobainis/Sites/html/pruebas/gn3-sts.crt");
    echo '<b><br>TOKEN DECODED: </b>';
    echo '<pre>';
    print_r($jwt_decodedSAML->decode($tokenSAML));
    echo '</pre>';
    echo '<br>';
    echo '<b>RESOURCE:</b> ' . $contentSAML;

    

    //var_dump($oauthclient);
    /*
    $token = $oauthclient->getAccess_token() . '<br>';
    print_r($token);
    echo '<br>' . $oauth->getClient_id();
    $array = explode(':', $token);
    
    echo '<br>';
    $a = $array[0];
    $b = base64_decode($array[1]);
    $c = base64_decode($array[2]);
    $d = base64_decode($array[3]);
    $e = base64_decode($array[4]);
    echo '<br>' . $a . ', ' . $b . ', ' . $c . ', ' . $d . ', ' . $e . '.<br><br>';
    //print_r($array);

    echo '<br>' . $array[0] . '<br>';
    $token = $array[1] . ':' . $array[2] . ':' . $array[3] . ':'  . $array[4];
    echo '<br>' . $token . '<br>';
    $decrypt = hash_hmac('sha256', $token, 'clave_prueba');
    echo $decrypt;
    */

?>
