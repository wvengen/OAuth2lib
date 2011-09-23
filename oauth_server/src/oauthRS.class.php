<?php

include_once('ErrorList.class.php');
include_once('AuthServerList.class.php');
include_once('LoadResourceConfig.class.php');

include_once('ResServerJWT.php');

class oauthRS {

    protected $error;
    protected $debug_active;
    protected $authservers;
    protected $errors;
    protected $scope;
    protected $extra;
    protected $resource;
    protected $token;
    protected $token_info;
    protected $token_format;
    protected $config_dir;

    public function __construct($dir= "") {
        if (0 == strcmp($dir, "")) {
            $this->config_dir = dirname(dirname(__FILE__)) . "/config/";
        } else {
            $this->config_dir = $dir;
            $last_char = substr($dir, strlen($dir) - 1);
            if (strcmp("/", $last_char) != 0) {
                $this->config_dir .= "/";
            }
        }
        $this->authservers = new AuthServerList($this->config_dir);
        $this->error = null;
        $this->errors = new ErrorList($this->config_dir);
        $this->debug_active = true;
        $this->scope = null;
        $this->extra = array();
        $this->token = null;
        //$this->token_info = "";
        $this->token_info = array();
        $this->token_format = array();
        $this->resource = null;
    }

    private function error($string) {
        if ($this->debug_active) {
            error_log("OAuth_RS: " . $string);
        }
    }

    /**
     * Function that manages the request of the app client and return an appropiate response.
     * Checks the format of the request depending on the method: GET, POST or header and if the given token is a valid one.
     * @return String with the Error or the Resource
     */
    public function manageRequest() {
        $this->error("manageRequest");
        if ($this->isValidFormatRequest()) {    // autenticación(si viene el token en la petición) (pasarlo al phpPoA)
            //if ($this->isValidToken()) {        // autorización(si el token es válido) (pasarlo al phpPoA)
            if ($this->isValidTokenSTS()) {        // autorización(si el token es válido) (pasarlo al phpPoA)
                $this->manageRSResponse();
            } else {
                $this->manageRSErrorResponse();
            }
        }
        if ($this->error != null) {
            $this->manageRSErrorResponse();
        }
    }

    public function isValidTokenSTS() {
        $valid = false;
        $res_jwt = new ResServerJWT("/Users/kurtiscobainis/Sites/html/pruebas/gn3-sts.crt");
        $request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sts="http://sts.wstrust.security.gembus.geant.net/">
                        <soapenv:Header/>
                        <soapenv:Body>
                            <ns4:RequestSecurityToken xmlns="http://www.w3.org/2005/08/addressing" xmlns:ns2="http://schemas.xmlsoap.org/ws/2004/09/policy" xmlns:ns3="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:ns4="http://docs.oasis-open.org/ws-sx/ws-trust/200512/" xmlns:ns5="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                            <ns4:RequestType>http://docs.oasis-open.org/ws-sx/ws-trust/200512/Validate</ns4:RequestType>
                            <ns4:TokenType>http://www.tokens.com/DefaultToken</ns4:TokenType>
                            <ns4:ValidateTarget><GemToken xmlns="urn:geant:gembus:security:token:1.0:gemtoken">' . $this->token . '</GemToken>
                        </ns4:ValidateTarget>
                        </ns4:RequestSecurityToken>
                   </soapenv:Body>
                   </soapenv:Envelope>';
        $opt = array('Content-Type' => 'text/xml; charset=UTF-8', 'SOAPAction' => "");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://bender.rediris.es:8197/GemSTSService/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $opt);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = "Curl error requesting the Access Token";
        } else {
            $pattern = "/Code\>http\:\/\/docs\.oasis\-open\.org\/ws\-sx\/ws\-trust\/200512\/status\/valid\<\//";
            if (preg_match($pattern, $output, $match_array) == 1)
                $valid = true;
            $claims = $res_jwt->decodeClaims($this->token);
            if (!$claims == NULL) {
                $this->token_info = $claims['token_info'];
                $this->scope = $claims['scope'];
                $this->processScope($this->scope);
                $this->createResource($this->scope);
                //$info = $this->addTokenInfo($this->token_info);
                //$info = $this->token_info;
                //var_dump(microtime(true));
                //var_dump($claims['exp']);
                if (!$this->authservers->checkAuthzKey($claims['authzID'])) {
                    $this->error = "Invalid AS key";
                    $valid = false;
                }
                if (!microtime(true) > $claims['exp']) {
                    $this->error = 'expired_token';
                    $valid = false;
                }
                //if (null==$info) {
                if (count($this->token_format) != count($this->token_info)) {
                    $this->error = "INSUFFICIENT_SCOPE";
                }
            }else
                $valid = false;
            //var_dump($output);
            //$info = curl_getinfo($ch);
            //file_put_contents('/Users/kurtiscobainis/Desktop/prueba.txt', $output, FILE_APPEND | LOCK_EX );
            //$dev = $this->processAuthServerResponse($info, $output);
        }
        curl_close($ch);
        return $valid;
    }

    /**
     * Function that checks the format of the request, depending on the method: GET, POST or header.
     * @return <boolean> True if is a valid one, false otherwise.
     */
    private function isValidFormatRequest() {
        $this->error("isValidFormatRequest");
        $dev = false;
        if (array_key_exists("CONTENT_TYPE", $_SERVER) && isset($_POST) && sizeof($_POST) > 0
                && (strcmp($_SERVER['CONTENT_TYPE'], "application/x-www-form-urlencoded") == 0)) {
            $dev = $this->isValidFormatGETorPOSTRequest($_POST);
        } else if (isset($_GET) && (sizeof($_GET) > 0)) {
            $dev = $this->isValidFormatGETorPOSTRequest($_GET);
        } else {
            $dev = $this->isValidFormatHeaderRequest(apache_request_headers());
        }
        return $dev;
    }

    /**
     * Function that checks if the request  (GET or POST) is a valid one.
     * @param <Array> $request
     * @return boolean  True if is a valid one, false otherwise.
     */
    private function isValidFormatGETorPOSTRequest($request) {
        $this->error("isValidGetorPOSTRequest");
        $dev = false;
        foreach ($request as $k => $val) {
            if (strcmp($k, "oauth_token") == 0) {
                $this->token = $val;
                $dev = true;
            } else {
                $this->extra[$k] = $val;
            }
        }
        if (!$dev) {
            $this->error .= "invalid_request";
        }
        return $dev;
    }

    /**
     * Function that checks if the request  (header) is a valid one.
     * @param <Array> $request
     * @return boolean  True if is a valid one, false otherwise.
     */
    private function isValidFormatHeaderRequest($headers) {
        $this->error("isValidHeaderRequest");
        $dev = false;
        //var_dump($headers);
        if (array_key_exists("Authorization", $headers)) {
            $value = $headers['Authorization'];
            $array = explode("OAuth ", $value);
            if (count($array) < 2) {
                $this->error = "invalid_request";
            } else {
                //$array = explode(",", $array[1]);
                $this->token = $array[1]; //array[0];
                if (count($array) > 1) {
                    $filtro = '/(.*)="(.*?)"/';
                    foreach ($array as $value) {
                        if (1 == preg_match_all($filtro, $value, $out)) {
                            array_push($this->extra, array($out[1][0] => $out[2][0]));
                        }
                    }
                }
                if (isset($_REQUEST)) {
                    foreach ($_REQUEST as $id => $req) {
                        $this->extra[$id] = $req;
                        $this->error(print_r($this->extra, 1));
                    }
                }
            }
        }
        if ($this->token == null) {
            $this->error = "invalid_request";
        } else {
            $dev = true;
        }
        return $dev;
    }

    private function processScope($scope) {
        $s = explode("?", $scope);
        $this->scope = $s[0];
        if (isset($s[1])) {
            $atts = explode("&", $s[1]);
            foreach ($atts as $att) {
                $aux = explode("=", $att);
                $this->extra[$aux[0]] = $aux[1];
            }
        }
    }

    /**
     * Function that checks if the token given in the request is a valid one.
     * @return <bool>  true if the token is a valid one
     * Modified by LuiJa for oauth2lib v14
     */
    private function isValidToken() {

        $res = true;
        $res_jwt = new ResServerJWT("/Users/kurtiscobainis/Sites/html/pruebas/gn3-sts.crt");
        $claims = $res_jwt->decode($this->token);
        if (!$claims == NULL) {
            $this->token_info = $claims['token_info'];
            $this->scope = $claims['scope'];
            $this->processScope($this->scope);
            $this->createResource($this->scope);
            //$info = $this->addTokenInfo($this->token_info);
            //$info = $this->token_info;
            //var_dump(microtime(true));
            //var_dump($claims['exp']);
            if (!$this->authservers->checkAuthzKey($claims['authzID'])) {
                $this->error = "Invalid AS key";
                $res = false;
            }
            if (!microtime(true) > $claims['exp']) {
                $this->error = 'expired_token';
                $res = false;
            }
            //if (null==$info) {
            if (count($this->token_format) != count($this->token_info)) {
                $this->error = "INSUFFICIENT_SCOPE";
            }
        }else
            $res = false;
        return $res;
    }

    /**
     * Function that checks if the scope included in the request is a valid one.
     * @param <type> $person_id
     * @return boolean
     * Not in use at the moment
     */
    private function addTokenInfo($token_info) {
        $this->error('addTokenInfo');
        $dev = null;
        //$token_info_attrs = explode("&&",$this->token_info);
        if (count($this->token_format) == count($token_info)) {
            $dev = array_combine($this->token_format, $token_info);
        }
        //  $dev = $this->resource->checkScope($this->scope, $token_info);
        return $dev;
    }

    /**
     * Function that manage a negative response.
     * If the error is insufficient_scope, sends a HTTP 403
     * If the error is a invalid_request, sends a HTTP 400
     * If the error is a invalid_token, sends a HTTP 401
     * Other types of errors returns an HTTP 401
     */
    private function manageRSErrorResponse() {
        if (0 == strcmp($this->error, "insufficient_scope")) {
            header("HTTP/1.0 403 Forbidden");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => $this->error, "scope" => $this->scope));
        } else if (0 == strcmp($this->error, "invalid_request")) {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => $this->error));
        } else if (0 == strcmp($this->error, "invalid_token")) {
            header("HTTP/1.0 401 Unauthorized");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => $this->error));
        } else {
            //expired-token
            header("HTTP/1.0 401 Unauthorized");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => $this->error));
        }
    }

    /**
     * Function that returns the resource, making use of the Resource Class deployed in the server.
     */
    private function manageRSResponse() {
        if ($this->resource != null) {
            $res = $this->resource->getResource($this->scope, $this->extra);
            if ($res == null) {
                $this->error = "invalid_token";
                $this->manageRSErrorResponse();
            } else {
                if ($this->resource->hasHeader()) {
                    foreach ($this->resource->getHeader() as $line) {
                        header($line);
                    }
                }
                echo $res;
            }
        } else {
            $this->error = "own_invalid_token";
            $this->manageRSErrorResponse();
        }
    }

    /**
     * Function that returns, by reflection, the IServerResource Class depending on the scope of the request.
     * @return null if an error exists
     */
    private function createResource() {
        $this->error("createResource");
        $conf = new LoadResourceConfig($this->config_dir);
        if ($conf->hasClass($this->scope)) {
            $class = $conf->getClass($this->scope);
            if ($conf->hasArchiveName($this->scope)) {
                include_once 'resources/' . $conf->getArchiveName($this->scope);
                $reflect = new ReflectionClass($class);
                $this->resource = $reflect->newInstance();
                if ($conf->hasArchiveName($this->scope)) {
                    $this->token_format = $conf->getTokenFormats($this->scope);
                } else {
                    $this->error = "invalid-resource-configuration3";
                }
            } else {
                $this->error = "invalid-resource-configuration";
            }
        } else {
            $this->error = "invalid-resource-configuration2";
        }
    }

}

?>