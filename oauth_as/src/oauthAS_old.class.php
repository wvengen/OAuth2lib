<?php
/**
 * OAuthAS
 * Class with the OAuth Authorization Server's logic
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_as
 */
include_once('assertions/saml2AC.class.php');
include_once('assertions/sirAC.class.php');
include_once('ErrorList.class.php');
include_once('ClientList.class.php');
include_once('ServerKeys.class.php');

// ************ JWT ************ //

include_once('PWT.php');



class oauthAS {
    const SAML2 = "urn:oasis:names:tc:SAML:2.0:assertion";
    const PAPI = "urn:mace:rediris.es:papi";

    protected $error;
    protected $debug_active;
    protected $clients;
    protected $servers;
    protected $lifetime;
    protected $assertion_checking;
    protected $scope;
    protected $assertion;
    protected $assertion_type;
    protected $client_id;
    protected $access_token;
    protected $errors;
    protected $config_dir;

    public function __construct($dir="") {
        if(0==strcmp($dir, "")){
            $this->config_dir = dirname(dirname(__FILE__)) . "/config/";
        }else{
           $this->config_dir = $dir;
           $last_char = substr($dir,strlen($dir)-1);
           if(strcmp("/",$last_char)!=0){
                 $this->config_dir .="/";
            }
        }
        $this->clients = new ClientConfiguration($this->config_dir);
        $this->error = null;
        $this->errors = new ErrorList($this->config_dir);
        $this->servers = new ServerKeys($this->config_dir);
        $this->lifetime = 3600;
        $this->assertion_checking = null;
        $this->debug_active = true;
        $this->assertion = null;
        $this->client_id = null;
        $this->assertion_type = null;
        $this->scope = null;
        $this->access_token = null;


    }

    private function error($string) {
        if ($this->debug_active) {
            error_log("OAuth_AS: " . $string);
        }
    }

    /**
     * Function that manages the request of the app client and return an appropiate response.
     * Return an HTTP 400 Bad Request with a json_encode error in the body of the response
     * @param <Array> $post  Parameters of the request. It contains:
     *      - grant_type [REQUIRED]  The access grant type included in the request.
     *      Value MUST be one of "authorization-code", "basic-credentials",
     *      "assertion", "refresh-token", or "none".
     *      - scope [OPTIONAL] The scope of the access request.
     *      - assertion_type [REQUIRED]  The format of the assertion as defined by the
     *      authorization server.  The value MUST be an absolute URI. The types supported by this
     *      library are PAPI assertion and SAML2 assertion.
     *      - assertion  REQUIRED.  The assertion.
     *      - client_id    REQUIRED.  The client identifier.
     */
    public function manageRequest($post) {
        $this->error("manageRequest");
        if ($this->isValidFormatRequest($post)) {
            if ($this->isValidScope()) {
                if ($this->isValidClient()) {
                    if ($this->isValidAssertion()) {
                        $this->generateAccessToken();
                        $this->manageASResponse();
                        $this->setLogMsg();
                    }
                }
            }
        }
        if ($this->error != null) {
            $this->manageASErrorResponse();
        }
    }

    /**
     * Function that checks if the request has a valid format
     * @param <Array> $post  Parameters of the request.
     * @return <bool> True if it is a valid format, false otherwise.
     */
    private function isValidFormatRequest($search) {
        $this->error("isValidFormatRequest");
        $res = false;
        if (is_array($search)
                && array_key_exists("grant_type", $search)
                && (strcmp($search["grant_type"], "assertion") == 0)
                && array_key_exists("assertion_type", $search)
                && ($search['assertion_type'] != "")
                && array_key_exists("assertion", $search)
                && ($search['assertion'] != "")
                && array_key_exists("client_id", $search)
                && ($search['client_id'] != "")) {
            $this->assertion = $search['assertion'];
            $this->assertion_type = $search['assertion_type'];
            $this->client_id = $search['client_id'];
            if (array_key_exists("scope", $search)) {
                $this->scope = $search['scope'];
            }
            $res = true;
        } else if (array_key_exists("grant_type", $search)
                && strcmp($search["grant_type"], "assertion") != 0) {
            $this->error = "unsupported_grant_type";
        } else {
            $this->error = "invalid_request";
        }
        return $res;
    }

    /**
     * Needed to avoid problems unserializing
     */
    private function transformArray($array) {
        $this->error("transformArray");
        $aux = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $key = str_replace("_dos_puntos_", ":", $key);
                $aux[$key] = $this->transformArray($value);
            } else {
                $key = str_replace("_dos_puntos_", ":", $key);
                $value = str_replace("_dos_puntos_", ":", $value);
                $aux[$key] = $value;
            }
        }
        return $aux;
    }

    /**
     * Function that checks if the scope is a valid one.
     * @return <bool> True if it is a valid one, false otherwise
     */
    private function isValidScope() {
        $this->error("isValidScope");
        $res = false;
        if(array_key_exists($this->cleanScope($this->scope), $this->servers)){
            $this->error = "invalid_scope";
        } else {
            $res = true;
        }
        return $res;
    }

  private function cleanScope($scope){
      $pos =strpos($scope,"?");
        if($pos===false){
            $res = $scope;
        }else{
            $res =  substr($scope, 0,$pos);
        }
        return $res;
    }

    /**
     * Funcion that checks if the client making the request is an authorized one.
     * @return <bool> True if it is a valid one, false otherwise
     */
    private function isValidClient() {
        $this->error("isValidClient");
        $res = false;
        $auth = apache_request_headers();
        $authBasic = $auth['Authorization'];
        $filtro = '/^Basic (.*?)$/';
        if (1 == preg_match_all($filtro, $authBasic, $out)) {
            $hmac = $out[1][0];
            if ($this->clients->isClient($this->client_id)) {
                if (0 == strcmp($hmac, hash_hmac("sha256", $this->client_id, $this->clients->getSecret($this->client_id)))) {
                    $res = true;
                } else {
                    $this->error = "invalid_client";
                }
            } else {
                $this->error = "unauthorized_client";
            }
        } else {
            $this->error = "invalid_client";
        }
        return $res;
    }

    /**
     * Function that ckecks the assertion depending of the assertion type (SAML2, PAPI)
     * @return <bool> True if it is a valid one, false otherwise
     */
    private function isValidAssertion() {
        $this->error("isValidAssertion");
        $res = false;
        if (strcmp(OAuthAS::SAML2, $this->assertion_type) == 0) {
            $this->assertion_checking = new saml2AssertionChecking($this->cleanScope($this->scope),$this->config_dir);
        } else if (strcmp(OAuthAS::PAPI, $this->assertion_type) == 0) {
            $this->assertion_checking = new sirAssertionChecking($this->cleanScope($this->scope),$this->config_dir);
        }else{
            $this->error = "invalid_grant";
            return $res;
        }
       if ($this->assertion_checking->checkAssertion($this->assertion)) {
            $res = true;
        }
        if (!$res) {
            $this->error = "invalid_grant";
        }
        return $res;
    }

    /**
     * Function that generates an access token from the parameters.
     */
    private function generateAccessToken() {
//
//        $this->error("generateAccessToken");
//        $key = $this->clients->getSecret($this->client_id);
//        //1 microsecond = 1.0 × 10-6 seconds
//        $change = 0.000001;
//        $time = microtime(true) + $this->lifetime*$change;
//        $message = base64_encode($this->client_id) . ":"
//                . base64_encode($this->assertion_checking->getTokenInfo()) . ":"
//                        . base64_encode($this->scope).  ":"
//                                . base64_encode($time);
//        $token = hash_hmac("sha256", $message, $this->servers->getKey($this->cleanScope($this->scope))) . ":" . $message;
//        $this->access_token = $token;



        PWT::init($this->client_id,"file://./mykey.pem");
        $pwt = new PWT(PWT::query);
        $pwt->returnURL="http://wiki.rediris.es/";
        $pwt->audience=".rediris.es";
        $pwt->opoa="http://wiki.rediris.es/";
        $pwt->hli="newharpo";
        $wire = $pwt->encode();  // llama a buildJWT y este a buildJSON
        $this->access_token = $pwt->dumpJWT();
        //var_dump($this->access_token);


//$pwt->returnURL="http://wiki.rediris.es/";
//$pwt->audience=".rediris.es";
//$pwt->opoa="http://wiki.rediris.es/";
//$pwt->hli="newharpo";
//$wire = $pwt->encode();
//$var = $pwt->getJWT();
//var_dump($var);

    }

    /**
     * Function that respond to the Client wiht the access token.
     * It is a response with a 200 (OK) status code and the following parameters:
     *  - access_token      REQUIRED.  The access token issued by the authorization server.
     *  - expires_in      OPTIONAL.  The duration in seconds of the access token  lifetime (s).
     *  - scope  OPTIONAL.  The scope of the access token
     */
    private function manageASResponse() {
        $this->error("manageASResponse");
        header("HTTP/1.0 200 Ok");
        header("Content-Type: application/json");
        header("Cache-control:no-store");
        $response = array();
        $response['access_token'] = $this->access_token;
        $response['expires_in'] = $this->lifetime;
        if ($this->scope != null && $this->scope != "") {
            $response['scope'] = $this->scope;
        }
        echo json_encode($response);
    }

    /**
     *  Responds an error If the token request is invalid or unauthorized by adding
     *  the following parameter to the entity body of the HTTP response using the
     *  "application/json"  media type:
     *  -  error  REQUIRED.  A single error code
     *  -  error_description  OPTIONAL.  A human-readable text providingç
     *      additional information, used to assist in the understanding and
     *      resolution of the error occurred.
     * -  error_uri  OPTIONAL.  A URI identifying a human-readable web page
     *     with information about the error, used to provide the end-user
     *     with additional information about the error.
     */
    private function manageASErrorResponse() {
        $this->error("manageASErrorResponse");
        header("HTTP/1.0 400 Bad Request");
        header("Content-Type: application/json");
        header("Cache-control:no-store");
        $err = array();
        if (!$this->errors->hasError($this->error)) {
            $err['error'] = $this->error;
            if ($this->errors->hasDescription($this->error)) {
                $err["error_description"] = $this->errors->getDescription($this->error);
            }
            if ($this->errors->hasURI($this->error)) {
                $err["error_uri"] = $this->errors->getURI($this->error);
            }
        } else {
            $err["error"] = "Infernal Error : Error unknown";
        }
        echo json_encode($err);
    }

    //Simple Getter
    public function getError() {
        return $this->error;
    }

    public function getConfig_dir() {
        return $this->config_dir;
    }

    public function setConfig_dir($config_dir) {
        $this->config_dir = $config_dir;
    }

    private function setLogMsg(){
        $file = fopen("oauth_access.log", "a");
        $string = "Token Request";
        $array = array("client_id"=>$this->client_id, "scope"=>$this->scope, "date"=>date(DATE_RFC822), "assertion"=>  serialize($this->assertion));
        $string.= ": ".json_encode($array)."\n";
        fwrite($file, $string);
    }


}
/*
PWT::init('client_id',"file://./mykey.pem");
$pwt = new PWT(PWT::query);
$pwt->returnURL="http://wiki.rediris.es/";
$pwt->audience=".rediris.es";
$pwt->opoa="http://wiki.rediris.es/";
$pwt->hli="newharpo";
$wire = $pwt->encode();

print_r($pwt);
echo '<hr>';
$pwt->PWTinfo();

*/

PWT::init('client_id',"file://./mykey.pem");
$pwt = new PWT(PWT::query);
$pwt->returnURL="http://wiki.rediris.es/";
$pwt->audience=".rediris.es";
$pwt->opoa="http://wiki.rediris.es/";
$pwt->hli="newharpo";
$wire = $pwt->encode();
$var = $pwt->dumpJWT();
var_dump($var);
echo '<hr>';
var_dump($wire);
$rec = new PWT();
$pwt->publicKeys('file://./publickey.pem');

$pwt->decode($wire);

?>