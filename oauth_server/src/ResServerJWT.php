<?php
/**
 * Description of ResServerJWT
 *
 * @author LuiJa
 * @pakkage oauth_server
 */
class ResServerJWT {

    protected $pubKeyDir;
    protected $jwt;
    protected $jwt_claims;

    public function  __construct($pubKeyDir) {
        $this->pubKeyDir = $pubKeyDir;
    }

    public function decode($jwt_encoded){
        //$this->jwt = ResServerJWT::base64urldecode($jwt_encoded);
        $this->jwt = $jwt_encoded;
        return $this->readJSON();

    }

    public static function base64urldecode($in){
        $iv1 = str_replace("_","/",$in);
	$iv2 = str_replace("-","+",$iv1);
	switch (strlen($iv2) % 4) {
		case 0:  break;
		case 2:  $iv2 .= "=="; break;
		case 3:  $iv2 .= "="; break;
		default: exit("FIXME: Illegal base64ulr string");
	}
	return base64_decode($iv2);

    }

    protected function readJSON(){
        $res = NULL;
        if($this->readJWT()){
            $res['typ'] = $this->jwt_claims['typ'];
            $res['iss'] = $this->jwt_claims['iss'];
            $res['iat'] = $this->jwt_claims['iat'];
            $res['exp'] = $this->jwt_claims['exp'];
            $res['client_id'] = $this->jwt_claims['client_id'];
            $res['token_info'] = $this->jwt_claims['token_info'];
            $res['scope'] = $this->jwt_claims['scope'];
            $res['authzID'] = $this->jwt_claims['authzID'];
        }
        //var_dump($res);
        return $res;
    }
    
    protected function readJWT(){
        $dev = false;
        $jwt_parts = explode(".", $this->jwt);
        $jwt_headers = json_decode(ResServerJWT::base64urldecode($jwt_parts[0]), true);
        if ($jwt_headers['typ'] != 'JWT' || $jwt_headers['alg'] != 'RS1' || $jwt_headers['pav'] != '2.0' ) {
            exit("FIXME: Invalid JWT header");
        }
        $jwt_signin_decoded = ResServerJWT::base64urldecode($jwt_parts[2]);
        $signin = $jwt_parts[0] . '.' . $jwt_parts[1];
        $fp = fopen($this->pubKeyDir, "r");
        $pubkey = fread($fp, 8192);
        fclose($fp);
        $pubkeyid = openssl_get_publickey($pubkey);        
        $ok = openssl_verify($signin, $jwt_signin_decoded, $pubkeyid);                
        openssl_free_key($pubkeyid);
        if($ok)
                $dev = true;
        $this->jwt_claims = json_decode(ResServerJWT::base64urldecode($jwt_parts[1]), true);        
        return $dev;
        
    }

    public function decodeClaims($jwt_encoded){
        $claims = array();
        $jwt_parts = explode(".", $jwt_encoded);
        $claims = json_decode(ResServerJWT::base64urldecode($jwt_parts[1]), true);
        return $claims;
    }


    public function getPubKeyDir(){
        return $this->pubKeyDir;
    }

    public function getJWT(){
        return $this->jwt;
    }

    public function getJWTclaims(){
        return $this->jwt_claims;
    }

}
?>
