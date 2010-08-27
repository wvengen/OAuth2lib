<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));

require_once('ErrorList.class.php');
require_once('AuthServerList.class.php');
require_once('LoadResourceConfig.class.php');

class oauthRS {

    protected $error;
    protected $debug_active;
    protected $authservers;
    protected $errors;
    protected $scope;
    protected $extra;
    protected $resource;
    protected $token;
    protected $person_id;

    public function __construct() {
        $this->authservers = new AuthServerList();
        $this->error = null;
        $this->errors = new ErrorList();
        $this->debug_active = true;
        $this->scope = null;
        $this->extra = array();
        $this->token = null;
        $this->person_id = "";
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
        if ($this->isValidFormatRequest()) {
            if ($this->isValidToken()) {
                $this->manageRSResponse();
            } else {
                $this->manageRSErrorResponse();
            }
        }
        if ($this->error != null) {
            $this->manageRSErrorResponse();
        }
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
        if (array_key_exists("Authorization", $headers)) {
            $value = $headers['Authorization'];
            $array = explode("OAuth ", $value);
            if (count($array) < 2) {
                $this->error = "invalid_request";
            } else {
                $array = explode(",", $array[1]);
                $this->token = $array[0];
                if (count($array) > 1) {
                    $filtro = '/(.*)="(.*?)"/';
                    foreach ($array as $value) {
                        if (1 == preg_match_all($filtro, $value, $out)) {
                            array_push($this->extra, array($out[1][0] => $out[2][0]));
                        }
                    }
                }
                if(isset($_REQUEST)){
                    foreach($_REQUEST as $id => $req){
                        $this->extra[$id] = $req;
                        $this->error(print_r($this->extra,1));
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

    private function processScope($scope){
        $s = explode("?",$scope);
        $this->scope = $s[0];
        $atts = explode("&",$s[1]);
        foreach($atts as $att){
            $aux = explode("=",$att);
            $this->extra[$aux[0]] = $aux[1];
        }       
    }

    /**
     * Function that checks if the token given in the request is a valid one.
     * @return <bool>  true if the token is a valid one
     */
    private function isValidToken() {
        $this->error("isValidToken");
        $dev = false;
        $array = explode(":", $this->token);
        $digest = $array[0];
        $token = $array[1] . ':' . $array[2] . ':' . $array[3] . ':' . $array[4] . ':' . $array[5];
        $id_client = base64_decode($array[1]);
        if (!$this->authservers->checkTokenKey($token, $digest)) {
            $this->error = "invalid_token";
        } else {
            $this->person_id = base64_decode($array[2]);
            $this->scope = base64_decode($array[3]);
            $this->extra['sHO']=base64_decode($array[4]);
            $this->processScope($this->scope);
            $this->createResource($this->scope);
            if (!$this->checkPersonScope($this->person_id)) {
                $this->error = "insufficient_scope";
            } else {
                $time = base64_decode($array[5]);
                if (microtime(true) > $time) {
                    $dev = true;
                } else {
                    $this->error = 'expired_token';
                }
            }
        }
        return $dev;
    }

    /**
     * Function that checks if the scope included in the request is a valid one.
     * @param <type> $person_id
     * @return boolean
     */
    private function checkPersonScope($person_id) {
        $this->error('checkPersonScope');
        $dev = $this->resource->checkScope($this->scope, $person_id);
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
            $this->error = "invalid_token";
            $this->manageRSErrorResponse();
        }
    }

    /**
     * Function that returns, by reflection, the IServerResource Class depending on the scope of the request.
     * @return null if an error exists
     */
    private function createResource() {
        $this->error("createResource");
        $conf = new LoadResourceConfig();
        if ($conf->hasClass($this->scope)) {
            $class = $conf->getClass($this->scope);
            if ($conf->hasArchiveName($this->scope)) {
                $class_arch_name = $conf->getArchiveName($this->scope);
                include_once dirname(__FILE__) . "/resources/" . $class_arch_name;
                $reflect = new ReflectionClass($class);
                $this->resource = $reflect->newInstance();
                
            } else {
                $this->error = "invalid-resource-configuration";
            }
        } else {
            $this->error = "invalid-resource-configuration2";
        }
    }

}
?>