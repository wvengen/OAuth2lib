<?php

/**
 * OAuthClient
 * Class with the OAuth Client logic
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
class OAuthClient {
    const HEADER = "HTTP_Authorization_Header";
    const GET = "URI_Query_Parameter";
    const BODY = "Form-Encoded_Body_Parameter";

    protected $debug_active;
    protected $error;
    protected $client_id;
    protected $client_secret;
    protected $scope_ret;
    protected $access_token;
    protected $expires_in;
    protected $resource;
    protected $request_type;

    /**
     * Constructor
     * @param <String> $clientid
     * @param <String> $clientsecret
     * @param <Boolean> $debug  Active the debug mode
     */
    public function __construct($clientid, $clientsecret, $debug=false) {
        $this->error = null;
        $this->debug_active = $debug;
        $this->client_id = $clientid;
        $this->client_secret = $clientsecret;
        $this->access_token = null;
        $this->expires_in = null;
        $this->resource = null;
        $this->scope_ret = null;
        $this->request_type = null;
    }

    private function error($string) {
        if ($this->debug_active) {
            error_log("OAuth_Client: " . $string);
        }
    }

    /**
     * Function that mades theaccess token request to the AS.
     * @param <String> $as Authorization Server
     * @param <String> $scope The scope of the access request.
     * @param <String> $assertion The assertion
     * @param <String> $assertion_type The format of the assertion as defined by the authorization server.
     * @param <String> $grant_type The access grant type included in the request.
     * @return <bool> True if the request obtained an Access Token, false otherwise.
     */
    public function doAccessTokenRequest($as, $scope, $assertion, $assertion_type, $grant_type="assertion") {
        $this->error("requestAccessToken");
        $dev = false;
        if ($this->isntHTTPS($as)) {
            $this->error = "The Auth Server connection must be over https";
        } else {
            if ($this->doATRequest($as, $this->generateATRequest($scope, $assertion, $assertion_type, $grant_type))) {
                $dev = true;
            }
        }
        return $dev;
    }

    /**
     * Generates an access token request.
     * @param <String> $scope The scope of the access request.
     * @param <String> $assertion The assertion
     * @param <String> $assertion_type The format of the assertion as defined by the authorization server.
     * @param <String> $grant_type The access grant type included in the request.
     * @return <Array>  The request array
     */
    private function generateATRequest($scope, $assertion, $assertion_type, $grant_type) {
        $this->error("generateATRequest");
        $aux = array();
        $aux['grant_type'] = $grant_type;
        $aux['assertion_type'] = $assertion_type;
        $aux['assertion'] = $assertion;
        $aux['scope'] = $scope;
        $aux['client_id'] = $this->client_id;
        $aux['client_secret'] = $this->client_secret;
        return $aux;
    }

    /**
     * Makes the http post curl connection to requests the access token from the authorization server.
     * Stores the access token in the protected param 'access_token'. If an error occurs,
     * it stores the error in the protected param 'error'.
     * @param <String> $as The Authorization Server URL
     * @param <Array> $request  The request data
     * @return <bool> True if the Auth server response has an access token
     */
    private function doATRequest($as, $request) {
        $this->error("doATRequest");
        $password = hash_hmac("sha256", $this->client_id, $this->client_secret);
        $opt = 'Authorization:  Basic ' . $password;
        $dev = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $as);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($opt));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = "Curl error requesting the Access Token";
        } else {
            $info = curl_getinfo($ch);
            $dev = $this->processAuthServerResponse($info, $output);
        }
        curl_close($ch);
        return $dev;
    }

    /**
     * Manages the Auth server response
     * @param <String> $output CURL output
     * @return <bool> True if the Auth server response has an access token
     */
    private function processAuthServerResponse($info, $output) {
        $this->error("processAuthServerResponse");
        $dev = false;
        $output = $this->cleanHeader($output);
        if ($info['http_code'] == 200) {
            if (json_decode($output)) {
                $aux = (array) json_decode($output);
                $this->access_token = $aux['access_token'];
                if (isset($aux['expires_in'])) {
                    $this->expires_in = $aux['expires_in'];
                }
                if (isset($aux['scope'])) {
                    $this->scope_ret = $aux['scope'];
                }
                $dev = true;
            } else {
                $this->error = "Authorization Server's response format unknown.";
            }
        } else {
            $this->error = $output;
        }
        return $dev;
    }

    /**
     * Function that manage the request to the resource server.
     * @param <String> $rs The Resource server
     * @param <String> $request_type it could be $GET, $HEADER or $BODY
     * @param <Array> $extra Extra parameters added in case of necessity. Initialized by default to null.
     */
    public function requestResource($rs, $request_type, $extra=null) {
        $this->error("requestResource");
        $dev = false;
        if ($this->isntHTTPS($rs)) {
            $this->error = "The Resource Server connection must be over https";
        } else {
            if ($this->doResourceRequest($rs, $request_type, $this->generateResourceRequest($extra))) {
                $dev = true;
            }
        }
        return $dev;
    }

    /**
     * Generates the array of the resource request
     * @param <Array> $extra Array with extra parameters
     * @return <Array>  Parameters of the request
     */
    private function generateResourceRequest($extra) {
        $this->error("generateResourceRequest");
        $aux = array();
        $aux['token'] = $this->access_token;
        if ($extra != null) {
            $aux = array_merge($aux, $extra);
        }
        return $aux;
    }

    /**
     * Function that makes the resource request to the Resource server.
     * @param <String> $rs the Resource Server
     * @param <String> $request_type The request type. It could be $GET, $BODY, or $HEADER
     * @param <Array> $request The request
     * @return <bool> True if the request obtained the resource
     */
    private function doResourceRequest($rs, $request_type, $request) {
        $this->error("doResourceRequest");
        $this->request_type = $request_type;
        $dev = false;
        if (0 == strcmp(OAuthClient::HEADER, $request_type)) {
            $dev = $this->doHeaderResRequest($rs, $request);
        } else if (0 == strcmp(OAuthClient::GET, $request_type)) {
            $dev = $this->doGetResRequest($rs, $request);
        } else if (0 == strcmp(OAuthClient::BODY, $request_type)) {
            $dev = $this->doBodyResRequest($rs, $request);
        } else {
            $this->error = "Resource Request Type Unknown";
        }
        return $dev;
    }

    /**
     * Function that makes an CURL Header request
     * 5.1.1.  The Authorization Request Header Field
     * The "Authorization" request header field is used by clients to make
     * authenticated token requests.  The client uses the "token" attribute
     *   to include the access token in the request.  For example:
     *      GET /resource HTTP/1.1
     *     Host: server.example.com
     *      Authorization: Token token="vF9dft4qmT"
     *    The "Authorization" header field uses the framework defined by
     *    [RFC2617] as follows:
     *      credentials    = "Token" RWS access-token [ CS 1#auth-param ]
     *      access-token   = "token" "=" <"> token <">
     *      CS             = OWS "," OWS
     * @param <String> $rs the Resource Server
     * @param <Array> $request The request
     * @return <bool> True if the request obtained the resource
     */
    private function doHeaderResRequest($rs, $request) {
        $this->error("doHeaderResRequest");
        $dev = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rs);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $opt = 'Authorization:  OAuth ' . $request['token'];
        if (sizeof($request) > 1) {
            foreach ($request as $key => $val) {
                if (strcmp($key, "token") != 0) {
                    $opt.= ',oauth_' . $key . '="' . $val . '"';
                }
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($opt));
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = "Curl error requesting the resource";
        } else {
            $info = curl_getinfo($ch);
            $dev = $this->processResServerResponse($info, $output);
        }
        curl_close($ch);
        return $dev;
    }

    /**
     * 5.1.3.  Form-Encoded Body Parameter
     *   When including the access token in the HTTP request entity-body, the
     *    client adds the access token to the request body using the
     *    "oauth_token" parameter.
     *    The entity-body can include other request-specific parameters, in
     *    which case, the "oauth_token" parameters SHOULD be appended following
     *    the request-specific parameters, properly separated by an "&"
     *    For example, the client makes the following HTTP request using TLS
     *      POST /resource HTTP/1.1
     *      Host: server.example.com
     *      Content-Type: application/x-www-form-urlencoded     oauth_token=vF9dft4qmT
     * @param <String> $rs the Resource Server
     * @param <Array> $request The request
     * @return <bool> True if the request obtained the resource
     */
    private function doBodyResRequest($rs, $request) {
        $this->error("doBodyResRequest");
        $dev = false;
        $string = 'oauth_token=' . $request['token'];
        if (sizeof($request) > 1) {
            foreach ($request as $key => $val) {
                if (strcmp($key, "token") != 0) {
                    $string.= '&' . $key . '=' . $val;
                }
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rs);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = "Curl error requesting the resource";
        } else {
            $info = curl_getinfo($ch);
            $dev = $this->processResServerResponse($info, $output);
        }
        curl_close($ch);
        return $dev;
    }

    /**
     * 5.1.2.  URI Query Parameter
     *    When including the access token in the HTTP request URI, the client
     *    adds the access token to the request URI query component as defined
     *    by [RFC3986] using the "oauth_token" parameter.
     *   For example, the client makes the following HTTP request using
     *    transport-layer security:
     *      GET /resource?oauth_token=vF9dft4qmT HTTP/1.1
     *      Host: server.example.com
     *    The HTTP request URI query can include other request-specific
     *    parameters, in which case, the "oauth_token" parameters SHOULD be
     *    appended following the request-specific parameters, properly
     *    separated by an "&" character (ASCII code 38).
     *    For example:     http://example.com/resource?x=y&oauth_token=vF9dft4qmT
     * @param <String> $rs the Resource Server
     * @param <Array> $request The request
     * @return <bool> True if the request obtained the resource
     */
    private function doGetResRequest($rs, $request) {
        $this->error("doGetResRequest");
        $dev = false;
        $ch = curl_init();
        $string = "?";
        if (sizeof($request) > 1) {
            foreach ($request as $key => $val) {
                if (strcmp($key, "token") != 0) {
                    $string.= '&' . $key . '=' . $val;
                }
            }
        }
        $string.= 'oauth_token=' . $request['token'];
        curl_setopt($ch, CURLOPT_URL, $rs . $string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = "Curl error requesting the resource";
        } else {
            $info = curl_getinfo($ch);
            $dev = $this->processResServerResponse($info, $output);
        }
        curl_close($ch);
        return $dev;
    }

    /**
     * Function that process the resource server response, cleaning the response headers or storing the error obtained.
     * @param <String> $output
     * @return <bool> True if the request obtained the resource
     */
    private function processResServerResponse($info, $output) {
        $this->error("processResServerResponse");
        $dev = false;
        if ($info['http_code'] == 200) {
            if (0 == strcmp($this->request_type, OAuth::HEADER)) {
                $this->resource = $this->cleanHeader($output);
            } else {
                $this->resource = $output;
            }
            $dev = true;
        } else {
            $this->error = $output;
        }
        return $dev;
    }

    /**
     * Auxiliar function that clean the server response header
     * @param <String> $string the response
     * @return <String> The response without the header
     */
    private function OLDcleanHeader($string) {
        $this->error("oldcleanheader");
        $st="";
        $pattern = '/Content-Type:(.*)\/(.*)/';
        preg_match_all($pattern, $string, $matches);
        if (!isset($matches[2][0])) {
            $this->error = "Unknown response mime type";
        } else {
            $word = $matches[2][0];
            $pos = strpos($string, $word) + strlen($word);
            $st = substr($string, $pos);
        }
        return $st;
    }

     /*RedIRIS clean header*/
        private function cleanHeader($string) {
 	   $this->error("Clean Header: ");
        $st="";
		$pattern = '/\{(.*)\}/';
        preg_match_all($pattern, $string, $matches);
        if(isset($matches[0][0])){
           if ($matches[0][0]!=null && $matches[0][0]!="" ) {
              $st = $matches[0][0];
           }
        } else {
        	$pattern2= '/Content-Language: es\\r\\n\\r\\n(.*)/';
        	preg_match_all($pattern2,$string,$matches2);
        	if(isset($matches2[1][0])){
        		$st = $matches2[1][0];
        	}else{
        		$this->error = "Unknown response type";
        	}
        }       
        if(strcmp($st,"")==0){
            //Para pruebas en local
            $st=$this->OLDcleanHeader($string);
        }
        return $st;
    }

    /**
     * Function that checks if and url is https or http
     * @param String $url
     * @return bool true if is http, false if it is https
     */
    private function isntHTTPS($url) {
        $this->error("isHTTPS");
        return (false === stripos($url, "https://"));
    }

    /**
     * Function that avoid the problem serializing
     * @param <Array> $array the assertion to serialize
     * @return <Array>  the array parameters serialized
     */
    private function transformArray($array) {
        $this->error("transformArray");
        $aux = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $key = str_replace(":", "_dos_puntos_", $key);
                $aux[$key] = $this->transformArray($value);
            } else {
                $key = str_replace(":", "_dos_puntos_", $key);
                $value = str_replace(":", "_dos_puntos_", $value);
                $aux[$key] = $value;
            }
        }
        return $aux;
    }

    /**
     * Return the required resource.
     * The format of the resources can be different in each implementation.
     * The custom transformation of the resource format could be done in the private method 'transformResponse'
     * @return String
     */
    public function getResource() {
        return $this->resource;
    }

    /**
     * Function that returns a json structure with the error.
     * @return <String> The error
     */
    public function getJSONError() {
        $this->error("getJSONError");
        $dev = $this->error;
        if (!json_decode($this->error)) {
            $dev = json_encode(array("error" => $this->error));
        }
        return $dev;
    }

    /**
     * Function that returns a formatted string with the error.
     * @return <String> The error
     */
    public function getHTMLError() {
        $this->error("getHTMLError");
        $string = "<div class='error'>";
        if (json_decode($this->error)) {
            $aux = (array) json_decode($this->error);
            $string .="<h3>";
            if (isset($aux['error_uri'])) {
                $string.="<a href='" . $aux['error_uri'] . "'>" . $aux['error'] . "</a>";
            } else {
                $string.=$aux['error'];
            }
            $string.="</h3>";
            if (isset($aux['error_description'])) {
                $string.="<p>" . $aux['error_description'] . "</p>";
            }
        } else {
            $string .= $this->error;
        }
        $string.="</div>";
        return $string;
    }

    //Simple getters
    public function getAccess_token() {
        return $this->access_token;
    }

    public function getExpires_in() {
        return $this->expires_in;
    }

}
?>