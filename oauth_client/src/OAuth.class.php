<?php

/**
 * OAuth
 * Configuration class that gives an abstraction to the OAuth2 flow
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
 include('oauth_client/src/OAuthClient.class.php');
 include('oauth_client/src//LoadConfig.class.php');

class OAuth {
    const HEADER = "HTTP_Authorization_Header";
    const GET = "URI_Query_Parameter";
    const BODY = "Form-Encoded_Body_Parameter";

    const SAML2 = "urn:oasis:names:tc:SAML:2.0:assertion";
    const PAPI = "urn:mace:rediris.es:papi";

    const HTML = "HTML";
    const JSON = "JSON";

    //Assertion Types
    protected $error;
    protected $error_type;
    protected $debug_active;
    // Client Credentials
    protected $client_id;
    protected $client_secret;
    //Access Token request Data
    protected $assertion_type;
    protected $scope;
    protected $as;
    protected $grant_type;
    //Resource Token request Data
    protected $request_type;
    protected $resource;
    protected $rs;
    //LoadConfig
    protected $conf;

    /**
     * Constructor
     * @param <String> $clientid
     * @param <String> $clientsecret 
     */
    public function __construct($dir = "") {
        if ($dir == ''){
             $file = dirname(dirname(__FILE__)) . '/config/clientConfig.xml';
         }else{
             $file = $dir.'/clientConfig.xml';
         }

        $this->error = null;
        $this->resource = null;
        $this->conf = new LoadConfig($file);
        $this->debug_active = $this->conf->get_debug_active_client();
        $this->error_type = $this->conf->get_error_type();
        $this->client_id = $this->conf->get_client_id();
        $this->client_secret = $this->conf->get_client_secret();
        $this->scope = $this->conf->get_scope();
        $this->as = $this->conf->get_as();
        $this->grant_type = $this->conf->get_grant_type();
        $this->rs = $this->conf->get_rs();
        $this->assertion_type =  $this->conf->get_assertion_type();
        $this->request_type =  $this->conf->get_request_type();
    }

    private function error($string) {
        if ($this->debug_active) {
            error_log("OAuth: " . $string);
        }
    }

    /**
     * Function that gets the resource with an OAuth2 flow and stores it in the 'resource' parameter. (And it could be accesed by the method getResource)
     * @param <type> $assertion
     * @return boolean True if the flow went ok, false otherwise. The error description is stored in the 'error' parameter
     */
    public function doOAuthFlow($assertion) {
        $this->error("doOAuthFlow");
        $dev = false;
        $oauth = new oauthClient($this->client_id, $this->client_secret, true);
        if (!$oauth->doAccessTokenRequest($this->as, $this->scope, $assertion, $this->assertion_type, $this->grant_type)) {
            $this->error = $this->returnError($oauth);
        } else if (!$oauth->requestResource($this->rs, $this->request_type)) {
            $this->error = $this->returnError($oauth);
        } else {
            $this->resource = $this->returnResource($oauth);
            $dev = true;
        }
        return $dev;
    }

     private function cleanScope($scope){
        if(strpos($scope,"?")==0){
            $res = $scope;
        }else{
            $res =  substr($scope, 0, strpos($scope,"?"));
        }
        return $res;
    }

    /**
     * Function that given an OAuthClient object, formats the corresponding response depending on the scope of the request.
     * @param <OAuthClient> $oauth
     * @return <String> The formatted response
     */
    public function returnResource($oauth) {
        $this->error("returnResource");
        if ($this->conf->hasFormatClass($this->cleanScope($this->scope))) {
            $class = $this->conf->getFormatClass($this->cleanScope($this->scope));
            if ($this->conf->hasFormatArchiveName($this->cleanScope($this->scope))) {
                include_once $this->conf->getFormatArchiveName($this->cleanScope($this->scope));
                $reflect = new ReflectionClass($class);
                $string =  $reflect->newInstance()->formatResource($oauth->getResource());
            } else {
                $this->error("You must fill the clientConfig.xml file before getting the resource. FormatArchiveName missing.");
                $string = "<br/>You must fill the clientConfig.xml file before getting the resource.</br>";
            }
        } else {
            $this->error("Resource without formatting. Develop a FormattingResponse Class to improve the functionality.");
            $string = $oauth->getResource();
        }
        return $string;
    }

    /**
     * Function that given an OAuthClient object, formats the obtained error depending on the selected type in the
     * OAuth class:
     * If it is HTML returns an  html <div class="error"> with the message inside of the div element.
     * If it is JSON returns a json element with the format: '{"error":"error_description"}'
     * @param <OAuthClient> $oauth
     * @return <String> The formatted error
     */
    public function returnError($oauth) {
        $this->error("returnError");
        if (0 == strcmp($this->error_type, OAuth::HTML)) {
            return $oauth->getHTMLError();
        } else {
            return $oauth->getJSONError();
        }
    }

    //Setters of the Assertion type

    /**
     * Set the assertion type to a PAPI assertion
     */
    public function setPAPIAssertionType() {
        $this->error("setPAPIAssertionType");
        $this->assertion_type = OAuth::PAPI;
    }

    /**
     * Set the assertion type to a SAML2 assertion
     */
    public function setSAML2AssertionType() {
        $this->error("setSAML2AssertionType");
        $this->assertion_type = OAuth::SAML2;
    }

    //Setters of the error response

    /**
     * Set the Error Response to an HTML String
     */
    public function setHTMLErrorResponse() {
        $this->error("setHTMLErrorResponse");
        $this->error_type = OAuth::HTML;
    }

    /**
     * Set the Error Response to a JSON element
     */
    public function setJSONErrorResponse() {
        $this->error("setJSONErrorResponse");
        $this->error_type = OAuth::JSON;
    }

    // Setters of the Resource Request

    /**
     * Set the resource request type to a POST request.
     */
    public function setBODYResourceRequest() {
        $this->error("setBODYResourceRequest");
        $this->request_type = OAuth::BODY;
    }

    /**
     * Set the resource request type to a Authorization HEADER request.
     */
    public function setHEADERResourceRequest() {
        $this->error("setHEADERResourceRequest");
        $this->request_type = OAuth::HEADER;
    }

    /**
     * Set the resource request type to a GET request.
     */
    public function setGETResourceRequest() {
        $this->error("setGETResourceRequest");
        $this->request_type = OAuth::GET;
    }

    // SimpleGetters
    public function getAssertion_type() {
        return $this->assertion_type;
    }

    public function getScope() {
        return $this->scope;
    }

    public function getAs() {
        return $this->as;
    }

    public function getGrant_type() {
        return $this->grant_type;
    }

    public function getResource() {
        return $this->resource;
    }

    public function getRs() {
        return $this->rs;
    }

    public function getError() {
        return $this->error;
    }

    public function getError_type() {
        return $this->error_type;
    }

    public function getClient_id() {
        return $this->client_id;
    }

    public function getClient_secret() {
        return $this->client_secret;
    }

    public function getRequest_type() {
        return $this->request_type;
    }

    //Simple Setters
    public function setScope($scope) {
        $this->scope = $scope;
    }

    public function setAs($as) {
        $this->as = $as;
    }

    public function setGrant_type($grant_type) {
        $this->grant_type = $grant_type;
    }

    public function setRs($rs) {
        $this->rs = $rs;
    }

}
?>