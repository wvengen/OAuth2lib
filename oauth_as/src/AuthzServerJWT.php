<?php
/**
 * Description of OAuthJWT
 *
 * @author LuiJa
 * @package oauth_as
 */
class AuthzServerJWT {

    protected $jwt;
    protected $jsonClaims;
    protected $privKeyDir;  // $privKeyDir = file//./loquesea.pem

    public function  __construct($pKD){
        $this->privKeyDir = $pKD;   
    }

    public function encode($clientID, $tokenInfo, $scope, $authzServerID, $authServerID_encoded){
        $this->buildJSON($clientID, $tokenInfo, $scope, $authzServerID, $authServerID_encoded);
        $this->buildJWT();
        return AuthzServerJWT::base64urlencode($this->jwt);
    }

    public static function base64urlencode($in){
        // Encodes a string according to the JWT base64 format
        $iv1 = explode("=",base64_encode($in));
	$iv2 = str_replace("+","-",$iv1[0]);
	return str_replace("/","_",$iv2);
    }

    // Hay que mejorarla para que entren como parámetros las claims que vengan de la petición OAuth
    public function buildJSON($clientID, $tokenInfo, $scope, $authzServerID, $authServerID_encoded){
        $claims = array();
        $claims['iat'] = time();        
        $time = $claims['iat'] + 3600;
        $claims['exp'] = $time;
        $claims['typ'] = 'OAuthJWT';
        $claims['iss'] = $authzServerID;
        $claims['client_id'] =  $clientID;
        $claims['token_info'] = $tokenInfo;
        $claims['scope'] = $scope;
        // Para comprobar que AS tiene registrado al RS donde se hace la petición
        $claims['authzID'] = $authServerID_encoded;
        $this->jsonClaims = json_encode($claims);
    }
    
    protected  function buildJWT(){
        $jwt_headers = array("typ" => "JWT",
                             "alg" => "RS256",
                             "pav" => "2.0");
        $jwt_json_headers = AuthzServerJWT::base64urlencode(json_encode($jwt_headers));
        $jwt_json_claims = AuthzServerJWT::base64urlencode($this->jsonClaims);
        $jwt_signin_input = $jwt_json_headers . "." . $jwt_json_claims;

        $fp = fopen($this->privKeyDir, "r");
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $privKey = openssl_get_privatekey($priv_key);
        openssl_sign($jwt_signin_input, $crypted, $privKey, OPENSSL_ALGO_SHA1);
        openssl_free_key($privKey);
        $jwt_signin = AuthzServerJWT::base64urlencode($crypted);
        $this->jwt = $jwt_signin_input . "." . $jwt_signin;

    }


    public function getJWT(){
        return $this->jwt;
    }

    public function getClaims(){
        return $this->jsonClaims;
    }

}


//$var = new AuthzServerJWT("/Users/kurtiscobainis/Sites/html/pruebas/mykey.pem");
//$jwt_encoded = $var->encode();
//var_dump($jwt_encoded);

?>
